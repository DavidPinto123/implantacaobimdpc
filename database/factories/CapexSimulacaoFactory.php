<?php

namespace Database\Factories;

use App\Models\CapexSimulacao;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CapexSimulacao>
 */
class CapexSimulacaoFactory extends Factory
{
    protected $model = CapexSimulacao::class;

    public function definition(): array
    {
        return [
            'projeto_id' => ProjetoFactory::new(),
            'nome' => fake()->company(),
            'sigla' => strtoupper(fake()->bothify('??##')),
            'endereco' => fake()->streetAddress(),
            'uf' => strtoupper(fake()->lexify('??')),
            'area_unidade' => fake()->randomFloat(2, 80, 1500),
            'fator_correcao' => fake()->randomFloat(2, 0.8, 1.5),
            'as_faixa_area_id' => null,
            'faixa_nome' => null,
            'custo_total_estimado' => fake()->randomFloat(2, 10000, 900000),
            'custo_por_m2' => fake()->randomFloat(2, 500, 8000),
            'status' => 0,
            'comentario' => null,
        ];
    }
}
