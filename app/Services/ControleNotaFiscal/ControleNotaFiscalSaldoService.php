<?php

namespace App\Services\ControleNotaFiscal;

use App\Enums\ModoSaldoFiscal;
use App\Enums\StatusControleNotaFiscalNota;
use App\Models\Asa;
use App\Models\AutorizacaoServico;
use App\Models\ControleNotaFiscalAuxiliar;
use App\Models\ControleNotaFiscalItem;

class ControleNotaFiscalSaldoService
{
    public function saldoParaAs(AutorizacaoServico $as, ModoSaldoFiscal $modo): float
    {
        $item = $as->controleNotaFiscalItem;
        $valorBase = $this->valorBaseItem($item);
        $notas = (float) $as->notasFiscais()
            ->whereIn('status', $this->statusComImpacto($modo))
            ->sum('valor_acumulado_medido_nf');

        return max(round($valorBase - $notas, 2), 0.0);
    }

    public function saldoParaAsa(Asa $asa, ModoSaldoFiscal $modo): float
    {
        $valorBase = $this->valorBaseAuxiliar($asa->controleNotaFiscalAuxiliar);
        $notas = (float) $asa->notasFiscais()
            ->whereIn('status', $this->statusComImpacto($modo))
            ->sum('valor_acumulado_medido_nf');

        return max(round($valorBase - $notas, 2), 0.0);
    }

    /**
     * @return array<int, string>
     */
    public function statusComImpacto(ModoSaldoFiscal $modo): array
    {
        return match ($modo) {
            ModoSaldoFiscal::Realizado => [StatusControleNotaFiscalNota::APROVADO->value],
            ModoSaldoFiscal::Comprometido => StatusControleNotaFiscalNota::comImpactoNoSaldo(),
        };
    }

    protected function valorBaseItem(?ControleNotaFiscalItem $item): float
    {
        if (! $item instanceof ControleNotaFiscalItem) {
            return 0.0;
        }

        return $this->firstPositiveValue([
            $item->valor_global_a,
            $item->saldo,
            $item->total_medicao_a_menos_b,
        ]);
    }

    protected function valorBaseAuxiliar(?ControleNotaFiscalAuxiliar $auxiliar): float
    {
        if (! $auxiliar instanceof ControleNotaFiscalAuxiliar) {
            return 0.0;
        }

        return $this->firstPositiveValue([
            $auxiliar->valor_global_a,
            $auxiliar->saldo,
            $auxiliar->total_medicao_a_menos_b,
        ]);
    }

    /**
     * @param  array<int, mixed>  $values
     */
    protected function firstPositiveValue(array $values): float
    {
        foreach ($values as $value) {
            $numericValue = (float) $value;

            if ($numericValue > 0) {
                return $numericValue;
            }
        }

        return 0.0;
    }
}
