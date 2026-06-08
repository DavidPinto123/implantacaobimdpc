<?php

namespace Database\Factories;

use App\Models\ControleNotaFiscalAuxiliar;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ControleNotaFiscalAuxiliar>
 */
class ControleNotaFiscalAuxiliarFactory extends Factory
{
    protected $model = ControleNotaFiscalAuxiliar::class;

    public function definition(): array
    {
        return [
            'controle_nota_fiscal_id' => ControleNotaFiscalFactory::new(),
            'grupo' => 'Solicitação Cliente',
            'numero_as' => strtoupper(fake()->bothify('ASA-####')),
            'numero_complemento' => '',
            'escopo' => fake()->sentence(),
            'empresa' => fake()->company(),
            'percentual_total' => 100,
            'percentual_faturamento_mao_obra' => 60,
            'percentual_faturamento_material' => 40,
            'valor_global_a' => 1000,
            'total_medicao_a_menos_b' => 1000,
            'valor_acumulado_medido' => 0,
            'saldo' => 1000,
            'liberado_para_fornecedor_at' => now(),
            'sort_order' => 1,
        ];
    }
}
