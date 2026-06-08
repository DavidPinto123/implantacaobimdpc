<?php

namespace Database\Factories;

use App\Models\PosObra\AprovacaoFinalizacao;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AprovacaoFinalizacao>
 */
class AprovacaoFinalizacaoFactory extends Factory
{
    protected $model = AprovacaoFinalizacao::class;

    public function definition(): array
    {
        return [
            'pendencia_id' => PendenciaFactory::new(),
            'solicitado_por' => UserFactory::new(),
            'aprovado_por' => null,
            'status' => 'PENDENTE',
            'motivo_rejeicao' => null,
        ];
    }
}
