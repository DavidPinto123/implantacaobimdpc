<?php

namespace App\Services\ControleNotaFiscal;

use App\Models\AsEscopo;
use App\Models\ControleNotaFiscal;
use App\Models\ControleNotaFiscalItem;

class PreencheEscoposPadraoControleNotaFiscal
{
    public function handle(ControleNotaFiscal $controle): void
    {
        AsEscopo::query()
            ->globais()
            ->where('is_active', true)
            ->where('is_personalizado', false)
            ->orderBy('grupo')
            ->orderBy('numero_as')
            ->get()
            ->each(function (AsEscopo $escopo, int $index) use ($controle): void {
                ControleNotaFiscalItem::query()->firstOrCreate(
                    [
                        'controle_nota_fiscal_id' => $controle->id,
                        'as_escopo_id' => $escopo->id,
                    ],
                    [
                        'grupo' => $escopo->grupo,
                        'numero_as' => $escopo->numero_as,
                        'escopo' => $escopo->escopo,
                        'percentual_total' => 100,
                        'percentual_faturamento_mao_obra' => $escopo->percentual_faturamento_mao_obra_default ?? 60,
                        'percentual_faturamento_material' => $escopo->percentual_faturamento_material_default ?? 40,
                        'valor_global_a' => 0,
                        'total_medicao_a_menos_b' => 0,
                        'valor_acumulado_medido' => 0,
                        'saldo' => 0,
                        'sort_order' => $index,
                    ],
                );
            });
    }
}
