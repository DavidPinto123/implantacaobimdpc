<?php

namespace Database\Factories;

use App\Models\PosObra\MensagemWhatsapp;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MensagemWhatsapp>
 */
class MensagemWhatsappFactory extends Factory
{
    protected $model = MensagemWhatsapp::class;

    public function definition(): array
    {
        return [
            'pendencia_id' => null,
            'telefone' => fake()->numerify('55###########'),
            'direcao' => fake()->randomElement(['ENVIADA', 'RECEBIDA']),
            'mensagem' => fake()->sentence(),
            'tipo' => 'TEXTO',
            'midia_url' => null,
            'status_entrega' => fake()->randomElement(['ENVIADA', 'SENT', 'DELIVERED', 'READ']),
            'wamid' => fake()->uuid(),
        ];
    }
}
