<?php

namespace Database\Factories;

use App\Models\PosObra\ConversaWhatsapp;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConversaWhatsapp>
 */
class ConversaWhatsappFactory extends Factory
{
    protected $model = ConversaWhatsapp::class;

    public function definition(): array
    {
        return [
            'telefone' => fake()->unique()->numerify('55###########'),
            'pendencia_id' => null,
            'perfil' => fake()->randomElement(['LIDER', 'CONSTRUTORA', 'GESTOR']),
            'fase' => 'INICIO',
            'contexto' => [],
            'ultima_mensagem_at' => now(),
        ];
    }
}
