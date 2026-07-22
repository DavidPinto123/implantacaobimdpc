<?php

namespace App\Services;

use App\Models\Orcamento;
use App\Models\OrcamentoRevitItem;

class OrcamentoRevitSincronizador
{
    /**
     * Busca os itens mais recentes do Revit para o arquivo vinculado ao orçamento,
     * cria categorias/itens novos e atualiza os já existentes (casados por código).
     * Nunca remove itens — o que foi adicionado manualmente na plataforma é preservado.
     */
    public static function sincronizar(Orcamento $orcamento, bool $bumpRevisao = true): array
    {
        $codigoObra = trim((string) $orcamento->arquivo_revit);

        if ($codigoObra === '') {
            return ['novos' => 0, 'atualizados' => 0, 'mudou' => false];
        }

        $itensPorCategoria = OrcamentoRevitItem::where('codigo_obra', $codigoObra)
            ->orderBy('categoria')
            ->orderBy('ordem')
            ->get()
            ->groupBy('categoria');

        if ($itensPorCategoria->isEmpty()) {
            return ['novos' => 0, 'atualizados' => 0, 'mudou' => false];
        }

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

                $dados = [
                    'descricao'  => $itemRevit->descricao,
                    'unidade'    => $itemRevit->unidade ?: 'un',
                    'quantidade' => $itemRevit->quantidade,
                    'valor_mat'  => $itemRevit->valor_mat,
                    'valor_mo'   => $itemRevit->valor_mo,
                ];

                if ($itemExistente) {
                    $mudou = (string) $itemExistente->quantidade !== (string) $dados['quantidade']
                        || (string) $itemExistente->valor_mat !== (string) $dados['valor_mat']
                        || (string) $itemExistente->valor_mo !== (string) $dados['valor_mo']
                        || $itemExistente->descricao !== $dados['descricao']
                        || $itemExistente->unidade !== $dados['unidade'];

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

        $orcamento->revit_sincronizado_em = now();
        $orcamento->save();

        return ['novos' => $novos, 'atualizados' => $atualizados, 'mudou' => $mudou];
    }
}
