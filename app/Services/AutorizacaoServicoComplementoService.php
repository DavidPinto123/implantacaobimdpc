<?php

namespace App\Services;

use App\Enums\TipoUnidade;
use App\Models\AutorizacaoServico;
use App\Models\CapexSimulacaoItem;
use App\Models\ControleNotaFiscalItem;
use Illuminate\Support\Facades\DB;

class AutorizacaoServicoComplementoService
{
    public function normalizar(mixed $complemento): string
    {
        $valor = strtoupper(trim((string) $complemento));

        return $valor === '' ? '' : $valor;
    }

    public function gerarProximo(
        int $obraId,
        int $asEscopoId,
        ?int $ignorarControleItemId = null,
        ?int $capexSimulacaoId = null,
    ): string {
        return DB::transaction(function () use ($asEscopoId, $capexSimulacaoId, $ignorarControleItemId, $obraId): string {
            $sequencia = DB::table('autorizacao_servico_complemento_sequencias')
                ->where('obra_id', $obraId)
                ->where('as_escopo_id', $asEscopoId)
                ->lockForUpdate()
                ->first();

            if (! $sequencia) {
                DB::table('autorizacao_servico_complemento_sequencias')->insert([
                    'obra_id' => $obraId,
                    'as_escopo_id' => $asEscopoId,
                    'ultimo_numero' => $this->maiorNumeroComplementoExistente(
                        $obraId,
                        $asEscopoId,
                        $ignorarControleItemId,
                        $capexSimulacaoId,
                    ),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if ($this->complementosExistentes($obraId, $asEscopoId, $ignorarControleItemId, $capexSimulacaoId) === []) {
                    return '';
                }

                $sequencia = DB::table('autorizacao_servico_complemento_sequencias')
                    ->where('obra_id', $obraId)
                    ->where('as_escopo_id', $asEscopoId)
                    ->lockForUpdate()
                    ->first();
            }

            $proximoNumero = ((int) $sequencia->ultimo_numero) + 1;

            DB::table('autorizacao_servico_complemento_sequencias')
                ->where('id', $sequencia->id)
                ->update([
                    'ultimo_numero' => $proximoNumero,
                    'updated_at' => now(),
                ]);

            return 'C'.$proximoNumero;
        });
    }

    protected function maiorNumeroComplementoExistente(
        int $obraId,
        int $asEscopoId,
        ?int $ignorarControleItemId = null,
        ?int $capexSimulacaoId = null,
    ): int {
        return collect($this->complementosExistentes($obraId, $asEscopoId, $ignorarControleItemId, $capexSimulacaoId))
            ->map(fn (string $complemento): ?int => preg_match('/^C(\d+)$/', $complemento, $matches) ? (int) $matches[1] : null)
            ->filter()
            ->max() ?? 0;
    }

    /**
     * @return array<int, string>
     */
    public function complementosExistentes(
        int $obraId,
        int $asEscopoId,
        ?int $ignorarControleItemId = null,
        ?int $capexSimulacaoId = null,
    ): array {
        $autorizacoes = AutorizacaoServico::query()
            ->where('obra_id', $obraId)
            ->where('as_escopo_id', $asEscopoId)
            ->pluck('numero_complemento')
            ->all();

        $itensControle = ControleNotaFiscalItem::query()
            ->where('as_escopo_id', $asEscopoId)
            ->when($ignorarControleItemId !== null, fn ($query) => $query->whereKeyNot($ignorarControleItemId))
            ->whereHas('controleNotaFiscal', fn ($query) => $query
                ->where('obra_id', $obraId)
                ->where('tipo_unidade', TipoUnidade::EXPANSAO->value))
            ->pluck('numero_complemento')
            ->all();

        $itensSimulador = $capexSimulacaoId === null
            ? []
            : CapexSimulacaoItem::query()
                ->where('capex_simulacao_id', $capexSimulacaoId)
                ->where('as_escopo_id', $asEscopoId)
                ->pluck('numero_complemento')
                ->all();

        return collect([...$autorizacoes, ...$itensControle, ...$itensSimulador])
            ->map(fn (mixed $value): string => $this->normalizar($value))
            ->unique()
            ->values()
            ->all();
    }
}
