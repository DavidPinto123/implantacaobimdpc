<?php

namespace App\Services;

use App\Models\Orcamento;
use App\Models\OrcamentoRevitItem;
use App\Models\OrcamentoRevitSinapiItem;

class OrcamentoRevitSincronizador
{
    /**
     * Busca os itens mais recentes do Revit para o arquivo vinculado ao orçamento,
     * cria categorias/itens novos e atualiza os já existentes (casados por código).
     * Nunca remove itens — o que foi adicionado manualmente na plataforma é preservado.
     *
     * A base de preços (LPU/SINAPI) do orçamento define de qual tabela do Revit os
     * itens vêm — são bancos com esquemas diferentes: LPU separa mat/mo, SINAPI usa
     * um valor unitário único e traz metadados extras (grupo, tipo, UF, desoneração...).
     */
    public static function sincronizar(Orcamento $orcamento, bool $bumpRevisao = true): array
    {
        $codigoObra = trim((string) $orcamento->arquivo_revit);

        if ($codigoObra === '') {
            return ['novos' => 0, 'atualizados' => 0, 'mudou' => false];
        }

        $base = $orcamento->base_precos ?? 'LPU';

        $itens = self::buscarItens($codigoObra, $base);

        if ($itens->isEmpty()) {
            return ['novos' => 0, 'atualizados' => 0, 'mudou' => false];
        }

        $itensPorCategoria = $itens->groupBy('categoria');

        $orcamento->loadMissing('categorias.itens');

        $novos       = 0;
        $atualizados = 0;

        foreach ($itensPorCategoria as $nomeCategoria => $itensRevit) {
            $categoria = $orcamento->categorias->first(
                fn ($cat) => mb_strtolower(trim($cat->nome)) === mb_strtolower(trim($nomeCategoria))
            );

            if (! $categoria) {
                $categoria = $orcamento->categorias()->create([
                    'nome'  => $nomeCategoria,
                    'ordem' => $orcamento->categorias->count(),
                ]);
                $orcamento->categorias->push($categoria);
            }

            foreach ($itensRevit as $itemRevit) {
                $itemExistente = $categoria->itens->first(
                    fn ($item) => filled($item->codigo) && $item->codigo === $itemRevit->codigo
                );

                $dados = self::mapearDados($itemRevit, $base);

                if ($itemExistente) {
                    $mudou = (string) $itemExistente->quantidade !== (string) $dados['quantidade']
                        || (string) $itemExistente->valor_mat !== (string) $dados['valor_mat']
                        || (string) $itemExistente->valor_mo !== (string) $dados['valor_mo']
                        || $itemExistente->descricao !== $dados['descricao']
                        || $itemExistente->unidade !== $dados['unidade']
                        || $itemExistente->grupo_catalogo !== $dados['grupo_catalogo']
                        || $itemExistente->tipo !== $dados['tipo'];

                    if ($mudou) {
                        $itemExistente->update($dados);
                        $atualizados++;
                    }
                } else {
                    $novo = $categoria->itens()->create([
                        'codigo' => $itemRevit->codigo,
                        'ordem'  => $categoria->itens->count(),
                        ...$dados,
                    ]);
                    $categoria->itens->push($novo);
                    $novos++;
                }
            }
        }

        $mudou = ($novos + $atualizados) > 0;

        if ($mudou && $bumpRevisao) {
            $orcamento->revisao++;
        }

        // Metadados de cabeçalho (UF, desoneração, mês de referência, emissão) só existem
        // no lado SINAPI — são uniformes por lote sincronizado, usa o primeiro item.
        if ($base === 'SINAPI') {
            $referencia = $itens->first();
            $orcamento->uf              = $referencia->uf ?? $orcamento->uf;
            $orcamento->desoneracao     = $referencia->desoneracao ?? $orcamento->desoneracao;
            $orcamento->mes_referencia  = $referencia->mes_referencia ?? $orcamento->mes_referencia;
            $orcamento->data_emissao    = $referencia->data_emissao ?? $orcamento->data_emissao;
        }

        $orcamento->revit_sincronizado_em = now();
        $orcamento->save();

        return ['novos' => $novos, 'atualizados' => $atualizados, 'mudou' => $mudou];
    }

    public static function buscarItens(string $codigoObra, string $base)
    {
        if ($base === 'SINAPI') {
            return OrcamentoRevitSinapiItem::where('codigo_obra', $codigoObra)
                ->orderBy('categoria')
                ->orderBy('ordem')
                ->get();
        }

        return OrcamentoRevitItem::where('codigo_obra', $codigoObra)
            ->orderBy('categoria')
            ->orderBy('ordem')
            ->get();
    }

    public static function mapearDados($itemRevit, string $base): array
    {
        if ($base === 'SINAPI') {
            return [
                'descricao'      => $itemRevit->descricao,
                'grupo_catalogo' => $itemRevit->grupo_catalogo,
                'tipo'           => $itemRevit->tipo,
                'unidade'        => $itemRevit->unidade ?: 'un',
                'quantidade'     => $itemRevit->quantidade,
                'valor_mat'      => $itemRevit->valor_unitario,
                'valor_mo'       => 0,
            ];
        }

        return [
            'descricao'      => $itemRevit->descricao,
            'grupo_catalogo' => null,
            'tipo'           => null,
            'unidade'        => $itemRevit->unidade ?: 'un',
            'quantidade'     => $itemRevit->quantidade,
            'valor_mat'      => $itemRevit->valor_mat,
            'valor_mo'       => $itemRevit->valor_mo,
        ];
    }
}
