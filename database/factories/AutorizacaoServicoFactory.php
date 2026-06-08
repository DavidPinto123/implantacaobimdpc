<?php

namespace Database\Factories;

use App\Enums\AsStatus;
use App\Models\AutorizacaoServico;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AutorizacaoServico>
 */
class AutorizacaoServicoFactory extends Factory
{
    protected $model = AutorizacaoServico::class;

    public function definition(): array
    {
        return [
            'obra_id' => ObrasFactory::new(),
            'as_escopo_id' => AsEscopoFactory::new(),
            'construtora_id' => ConstrutoraFactory::new(),
            'status' => AsStatus::RASCUNHO,
            'numero_as' => strtoupper(fake()->bothify('AS-####')),
            'numero_complemento' => '',
            'valor' => 0,
            'desconto_autorizacao_servico' => 0,
            'valor_estimado' => 0,
            'observacoes' => fake()->sentence(),
        ];
    }
}
