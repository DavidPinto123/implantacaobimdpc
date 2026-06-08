<?php

namespace Database\Factories;

use App\Enums\TipoObraCronograma;
use App\Models\CronogramaTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CronogramaTemplate>
 */
class CronogramaTemplateFactory extends Factory
{
    protected $model = CronogramaTemplate::class;

    public function definition(): array
    {
        return [
            'nome' => 'Template '.fake()->unique()->words(2, true),
            'tipo_obra' => TipoObraCronograma::EXPANSAO,
            'ancora_campo' => 'projeto.inauguracao',
            'ativo' => true,
            'observacoes' => fake()->optional()->sentence(),
        ];
    }
}
