<?php

namespace Database\Factories;

use App\Models\Etapa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Etapa>
 */
class EtapaFactory extends Factory
{
    protected $model = Etapa::class;

    public function definition(): array
    {
        return [
            'nome' => fake()->unique()->word(),
        ];
    }
}
