<?php

namespace Database\Factories;

use App\Enums\AsStatus;
use App\Models\Asa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asa>
 */
class AsaFactory extends Factory
{
    protected $model = Asa::class;

    public function definition(): array
    {
        return [
            'numero_asa' => strtoupper(fake()->bothify('ASA-####')),
            'projeto_id' => ProjetoFactory::new(),
            'status' => AsStatus::SOLICITADO,
            'data_solicitacao' => fake()->date(),
            'objeto' => fake()->sentence(),
            'justificativa' => fake()->paragraph(),
            'valor_bruto' => 1000,
            'desconto' => 0,
            'valor_total' => 1000,
        ];
    }
}
