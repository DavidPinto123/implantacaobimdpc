<?php

namespace Database\Factories;

use App\Models\PosObra\WhatsappBotMensagem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WhatsappBotMensagem>
 */
class WhatsappBotMensagemFactory extends Factory
{
    protected $model = WhatsappBotMensagem::class;

    public function definition(): array
    {
        return [
            'chave' => fake()->unique()->lexify('teste.????'),
            'texto' => fake()->sentence(),
        ];
    }
}
