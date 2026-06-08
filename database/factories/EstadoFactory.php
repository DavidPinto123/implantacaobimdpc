<?php

namespace Database\Factories;

use App\Models\Estado;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Estado>
 */
class EstadoFactory extends Factory
{
    protected $model = Estado::class;

    public function definition(): array
    {
        return [
            'pais_id' => PaisFactory::new(),
            'nome' => fake()->state(),
            'uf' => strtoupper(fake()->lexify('??')),
        ];
    }
}
