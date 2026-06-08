<?php

namespace Database\Factories;

use App\Models\AsEscopo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AsEscopo>
 */
class AsEscopoFactory extends Factory
{
    protected $model = AsEscopo::class;

    public function definition(): array
    {
        return [
            'grupo' => fake()->randomElement(['civil', 'hidraulica', 'eletrica']),
            'numero_as' => strtoupper(fake()->unique()->bothify('AS-####')),
            'escopo' => fake()->unique()->sentence(3),
            'percentual_faturamento_mao_obra_default' => 60,
            'percentual_faturamento_material_default' => 40,
            'is_active' => true,
        ];
    }
}
