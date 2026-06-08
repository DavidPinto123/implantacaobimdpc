<?php

namespace Database\Factories;

use App\Models\Pais;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pais>
 */
class PaisFactory extends Factory
{
    protected $model = Pais::class;

    public function definition(): array
    {
        return [
            'nome' => fake()->unique()->country(),
        ];
    }
}
