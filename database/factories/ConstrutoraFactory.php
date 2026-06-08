<?php

namespace Database\Factories;

use App\Models\Construtora;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Construtora>
 */
class ConstrutoraFactory extends Factory
{
    protected $model = Construtora::class;

    public function definition(): array
    {
        return [
            'nome' => fake()->company(),
            'cnpj' => fake()->unique()->numerify('##############'),
            'telefone' => fake()->phoneNumber(),
            'email' => fake()->safeEmail(),
            'tipo' => 'CONSTRUTORA',
            'telefone_whatsapp' => fake()->phoneNumber(),
        ];
    }
}
