<?php

namespace Database\Factories;

use App\Models\Cidade;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cidade>
 */
class CidadeFactory extends Factory
{
    protected $model = Cidade::class;

    public function definition(): array
    {
        return [
            'nome' => fake()->city(),
            'estado_id' => EstadoFactory::new(),
        ];
    }
}
