<?php

namespace Database\Factories;

use App\Models\PosObra\AtualizacaoStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AtualizacaoStatus>
 */
class AtualizacaoStatusFactory extends Factory
{
    protected $model = AtualizacaoStatus::class;

    public function definition(): array
    {
        return [
            'pendencia_id' => PendenciaFactory::new(),
            'status_anterior' => 'REGISTRADA',
            'status_novo' => 'NOTIFICADA_PRESTADORA',
            'comentario' => fake()->sentence(),
            'atualizado_por' => fake()->name(),
        ];
    }
}
