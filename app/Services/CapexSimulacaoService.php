<?php

namespace App\Services;

use App\Models\AsFaixaArea;
use App\Models\CapexSimulacao;

class CapexSimulacaoService
{
    public function recalcularComDadosDoFormulario(
        CapexSimulacao $record,
        float $areaUnidade,
        float $fatorCorrecao
    ): void {
        $faixa = null;

        if ($areaUnidade > 0) {
            $faixa = AsFaixaArea::query()
                ->where('area_min', '<=', $areaUnidade)
                ->where(fn ($q) => $q->where('area_max', '>=', $areaUnidade)->orWhereNull('area_max'))
                ->orderBy('area_min')
                ->first();
        }

        $record->update([
            'area_unidade' => $areaUnidade,
            'fator_correcao' => $fatorCorrecao,
            'as_faixa_area_id' => $faixa?->id,
            'faixa_nome' => $faixa?->nome ?? ($areaUnidade > 0 ? 'FAIXA NÃO IDENTIFICADA' : null),
        ]);

        $record->recalcularItensAutomaticosETotais();
    }
}
