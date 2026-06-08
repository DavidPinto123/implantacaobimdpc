<?php

namespace Database\Factories;

use App\Enums\FaseCronograma;
use App\Enums\TipoDiasTemplate;
use App\Models\CronogramaTemplate;
use App\Models\CronogramaTemplateFase;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CronogramaTemplateFase>
 */
class CronogramaTemplateFaseFactory extends Factory
{
    protected $model = CronogramaTemplateFase::class;

    public function definition(): array
    {
        $fase = FaseCronograma::VISITA_TECNICA;

        return [
            'cronograma_template_id' => CronogramaTemplate::factory(),
            'fase' => $fase,
            'ordem' => $fase->ordem(),
            'duracao_dias' => fake()->numberBetween(0, 30),
            'tipo_dias' => TipoDiasTemplate::CORRIDOS,
            'visivel' => true,
            'is_ancora' => false,
            'observacoes' => fake()->optional()->sentence(),
        ];
    }
}
