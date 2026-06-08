<?php

namespace Database\Factories;

use App\Enums\FaseCronograma;
use App\Enums\StatusCronograma;
use App\Models\CronogramaFase;
use App\Models\Projeto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CronogramaFase>
 */
class CronogramaFaseFactory extends Factory
{
    protected $model = CronogramaFase::class;

    public function definition(): array
    {
        $fase = FaseCronograma::VISITA_TECNICA;

        return [
            'projeto_id' => Projeto::factory(),
            'obras_id' => null,
            'fase' => $fase,
            'ordem' => $fase->ordem(),
            'marco' => $fase->marco(),
            'cronograma_template_id' => null,
            'cronograma_template_fase_id' => null,
            'data_prevista_inicio' => now()->subDays(5)->toDateString(),
            'data_prevista_fim' => now()->addDays(5)->toDateString(),
            'data_realizada_inicio' => null,
            'data_realizada_fim' => null,
            'status' => StatusCronograma::NAO_INICIADO,
            'percentual_conclusao' => 0,
            'observacoes' => fake()->optional()->sentence(),
            'data_aprovacao' => null,
            'metadados' => null,
            'regra_duracao_dias' => null,
            'regra_tipo_dias' => null,
            'regra_customizada' => false,
            'visivel' => true,
        ];
    }
}
