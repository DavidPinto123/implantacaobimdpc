<?php

namespace Database\Factories;

use App\Models\ControleNotaFiscalItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ControleNotaFiscalItem>
 */
class ControleNotaFiscalItemFactory extends Factory
{
    protected $model = ControleNotaFiscalItem::class;

    public function definition(): array
    {
        return [
            'controle_nota_fiscal_id' => ControleNotaFiscalFactory::new(),
            'grupo' => fake()->randomElement(['civil', 'hidraulica', 'eletrica']),
            'numero_as' => fake()->numerify('AS####'),
            'escopo' => fake()->sentence(3),
            'empresa' => fake()->company(),
            'percentual_total' => 100,
            'percentual_faturamento_mao_obra' => 60,
            'percentual_faturamento_material' => 40,
            'valor_estimado_as' => 10000,
            'valor_global_a' => 10000,
            'total_medicao_a_menos_b' => 9500,
            'valor_acumulado_medido' => 1000,
            'saldo' => 8500,
            'sort_order' => 1,
        ];
    }
}
