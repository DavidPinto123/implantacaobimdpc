<?php

namespace Database\Factories;

use App\Models\Setor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Setor>
 */
class SetorFactory extends Factory
{
    protected $model = Setor::class;

    public function definition(): array
    {
        return [
            'setor' => fake()->unique()->word(),
        ];
    }
}
