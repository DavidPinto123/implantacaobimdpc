<?php

namespace Database\Factories;

use App\Models\ImportacaoStaging;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImportacaoStaging>
 */
class ImportacaoStagingFactory extends Factory
{
    protected $model = ImportacaoStaging::class;

    public function definition(): array
    {
        return [
            'importacao_log_id' => ImportacaoLogFactory::new(),
            'linha_planilha' => fake()->numberBetween(1, 500),
            'codigo' => strtoupper(fake()->bothify('OBR-####')),
            'acao' => fake()->randomElement(['criar', 'atualizar', 'erro']),
            'obra_existente_id' => null,
            'dados' => ['unidade' => fake()->company()],
            'conflitos' => null,
            'erro' => null,
        ];
    }
}
