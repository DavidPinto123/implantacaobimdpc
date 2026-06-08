<?php

namespace Database\Factories;

use App\Models\PosObra\WhatsappConfig;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WhatsappConfig>
 */
class WhatsappConfigFactory extends Factory
{
    protected $model = WhatsappConfig::class;

    public function definition(): array
    {
        return [
            'phone_number_id' => fake()->numerify('##########'),
            'token' => fake()->sha256(),
            'verify_token' => fake()->uuid(),
            'ativo' => true,
        ];
    }
}
