<?php

namespace App\Services;

use App\Enums\FaseCronograma;
use App\Models\CronogramaFase;
use App\Models\CronogramaFaseItem;
use App\Models\Projeto;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Importa datas de pré-vendas (Marketing) em lote a partir de uma planilha.
 *
 * Layout esperado (CSV/XLSX): código de unidade na 1ª coluna, data
 * pré-venda físico na 2ª, data pré-venda online na 3ª. Cabeçalho opcional.
 */
class ImportadorMktService
{
    private const CABECALHO_PADRAO = ['codigo', 'data_pre_venda_fisico', 'data_pre_venda_online'];

    /**
     * Faz parse de um arquivo CSV (1ª opção) ou de array de arrays (1ª coluna = código).
     *
     * @return Collection<int, array{linha:int, codigo:string, data_fisico:?string, data_online:?string, projeto_id:?int, projeto_nome:?string, conflito_fisico:?array, conflito_online:?array, erro:?string}>
     */
    public function preview(string $caminhoArquivo): Collection
    {
        $linhas = $this->lerCsv($caminhoArquivo);

        return collect($linhas)->map(function (array $linha, int $idx) {
            return $this->avaliarLinha($linha, $idx + 1);
        });
    }

    public function aplicar(Collection $preview, array $decisoes = []): array
    {
        $aplicados = 0;
        $ignorados = 0;
        $erros = 0;

        foreach ($preview as $entrada) {
            if (! empty($entrada['erro'])) {
                $erros++;
                continue;
            }

            $decisao = $decisoes[$entrada['linha']] ?? 'sobrescrever';
            if ($decisao === 'pular') {
                $ignorados++;
                continue;
            }

            $this->aplicarLinha($entrada, $decisao === 'manter_atual');
            $aplicados++;
        }

        return compact('aplicados', 'ignorados', 'erros');
    }

    private function lerCsv(string $caminhoArquivo): array
    {
        $linhas = [];
        if (! is_readable($caminhoArquivo)) {
            return $linhas;
        }

        $handle = fopen($caminhoArquivo, 'r');
        if ($handle === false) {
            return $linhas;
        }

        $primeiraLinha = true;
        while (($row = fgetcsv($handle, 4000, ',')) !== false) {
            if ($primeiraLinha && $this->ehCabecalho($row)) {
                $primeiraLinha = false;
                continue;
            }
            $primeiraLinha = false;

            $linhas[] = [
                'codigo' => trim((string) ($row[0] ?? '')),
                'data_fisico' => trim((string) ($row[1] ?? '')) ?: null,
                'data_online' => trim((string) ($row[2] ?? '')) ?: null,
            ];
        }
        fclose($handle);

        return $linhas;
    }

    private function ehCabecalho(array $row): bool
    {
        $primeiraCol = strtolower(trim((string) ($row[0] ?? '')));

        return in_array($primeiraCol, ['codigo', 'código', 'code', 'sigla'], true);
    }

    private function avaliarLinha(array $linha, int $numLinha): array
    {
        $resultado = [
            'linha' => $numLinha,
            'codigo' => $linha['codigo'],
            'data_fisico' => $this->normalizarData($linha['data_fisico']),
            'data_online' => $this->normalizarData($linha['data_online']),
            'projeto_id' => null,
            'projeto_nome' => null,
            'conflito_fisico' => null,
            'conflito_online' => null,
            'erro' => null,
        ];

        if (blank($linha['codigo'])) {
            $resultado['erro'] = 'Código vazio';

            return $resultado;
        }

        $projeto = Projeto::where('codigo', $linha['codigo'])->first();
        if (! $projeto) {
            $resultado['erro'] = "Projeto com código {$linha['codigo']} não encontrado";

            return $resultado;
        }

        $resultado['projeto_id'] = $projeto->id;
        $resultado['projeto_nome'] = $projeto->nome;

        $faseMkt = CronogramaFase::where('projeto_id', $projeto->id)
            ->where('fase', FaseCronograma::MKT_ATIVACAO_PRE_VENDAS->value)
            ->with('itens')
            ->first();

        if (! $faseMkt) {
            $resultado['erro'] = 'Fase MKT Ativação Pré-Vendas não encontrada no cronograma';

            return $resultado;
        }

        $fisico = $faseMkt->itens->first(fn ($i) => str_contains(strtolower($i->titulo), 'físico') || str_contains(strtolower($i->titulo), 'fisico'));
        $online = $faseMkt->itens->first(fn ($i) => str_contains(strtolower($i->titulo), 'online'));

        if ($fisico && $resultado['data_fisico'] && $fisico->data_prevista_inicio
            && $fisico->data_prevista_inicio->toDateString() !== $resultado['data_fisico']
        ) {
            $resultado['conflito_fisico'] = [
                'atual' => $fisico->data_prevista_inicio->toDateString(),
                'novo' => $resultado['data_fisico'],
            ];
        }
        if ($online && $resultado['data_online'] && $online->data_prevista_inicio
            && $online->data_prevista_inicio->toDateString() !== $resultado['data_online']
        ) {
            $resultado['conflito_online'] = [
                'atual' => $online->data_prevista_inicio->toDateString(),
                'novo' => $resultado['data_online'],
            ];
        }

        return $resultado;
    }

    private function aplicarLinha(array $entrada, bool $manterAtual): void
    {
        $faseMkt = CronogramaFase::where('projeto_id', $entrada['projeto_id'])
            ->where('fase', FaseCronograma::MKT_ATIVACAO_PRE_VENDAS->value)
            ->with('itens')
            ->first();

        if (! $faseMkt) {
            return;
        }

        if ($entrada['data_fisico']) {
            $fisico = $faseMkt->itens->first(fn ($i) => str_contains(strtolower($i->titulo), 'físico') || str_contains(strtolower($i->titulo), 'fisico'));
            if ($fisico && ! ($manterAtual && $fisico->data_prevista_inicio)) {
                $fisico->update([
                    'data_prevista_inicio' => $entrada['data_fisico'],
                    'data_prevista_fim' => $entrada['data_fisico'],
                ]);
            }
        }
        if ($entrada['data_online']) {
            $online = $faseMkt->itens->first(fn ($i) => str_contains(strtolower($i->titulo), 'online'));
            if ($online && ! ($manterAtual && $online->data_prevista_inicio)) {
                $online->update([
                    'data_prevista_inicio' => $entrada['data_online'],
                    'data_prevista_fim' => $entrada['data_online'],
                ]);
            }
        }
    }

    private function normalizarData(?string $valor): ?string
    {
        if (blank($valor)) {
            return null;
        }

        try {
            // Tenta vários formatos comuns
            foreach (['Y-m-d', 'd/m/Y', 'd-m-Y'] as $formato) {
                $dt = Carbon::createFromFormat($formato, $valor);
                if ($dt) {
                    return $dt->toDateString();
                }
            }

            return Carbon::parse($valor)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
