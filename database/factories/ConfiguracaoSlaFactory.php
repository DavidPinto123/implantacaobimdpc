<?php

namespace Database\Factories;

use App\Models\PosObra\ConfiguracaoSla;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConfiguracaoSla>
 */
class ConfiguracaoSlaFactory extends Factory
{
    protected $model = ConfiguracaoSla::class;

    public function definition(): array
    {
        return [
            'urgencia' => fake()->randomElement(['P1', 'P2', 'P3']),
            'prazo_horas' => fake()->randomElement([6, 12, 24]),
            'ativo' => true,
        ];
    }
}
