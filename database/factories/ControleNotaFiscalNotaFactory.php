<?php

namespace Database\Factories;

use App\Enums\StatusControleNotaFiscalNota;
use App\Models\ControleNotaFiscalNota;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ControleNotaFiscalNota>
 */
class ControleNotaFiscalNotaFactory extends Factory
{
    protected $model = ControleNotaFiscalNota::class;

    public function definition(): array
    {
        return [
            'autorizacao_servico_id' => AutorizacaoServicoFactory::new(),
            'autorizacao_servico_adicional_id' => null,
            'importado_por_id' => UserFactory::new()->active(),
            'tipo_medicao' => 'mao_obra',
            'empresa' => fake()->company(),
            'cnpj_fornecedor' => fake()->numerify('##############'),
            'numero_nf' => fake()->numerify('NF######'),
            'cnpj_faturamento' => fake()->numerify('##############'),
            'instrucoes_pagamento' => fake()->sentence(),
            'valor_acumulado_medido_nf' => 1000,
            'emissao' => fake()->date(),
            'envio' => fake()->date(),
            'status' => StatusControleNotaFiscalNota::PENDENTE->value,
            'sort_order' => 1,
        ];
    }

    public function forItem(): static
    {
        return $this->state(fn (): array => [
            'autorizacao_servico_id' => AutorizacaoServicoFactory::new(),
            'autorizacao_servico_adicional_id' => null,
        ]);
    }
}
