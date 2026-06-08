<?php

namespace Database\Factories;

use App\Models\PosObra\DisciplinaConfig;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DisciplinaConfig>
 */
class DisciplinaConfigFactory extends Factory
{
    protected $model = DisciplinaConfig::class;

    public function definition(): array
    {
        return [
            'codigo' => fake()->unique()->bothify('DISC-###'),
            'label' => fake()->words(2, true),
            'ativo' => true,
            'ordem' => fake()->numberBetween(1, 20),
        ];
    }
}
