<?php

namespace Database\Factories;

use App\Models\ImportacaoLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImportacaoLog>
 */
class ImportacaoLogFactory extends Factory
{
    protected $model = ImportacaoLog::class;

    public function definition(): array
    {
        return [
            'arquivo_original' => 'importacao-'.fake()->uuid().'.xlsx',
            'arquivo_path' => 'importacoes/'.fake()->uuid().'.xlsx',
            'modulo' => fake()->randomElement(['obras', 'cnpjs']),
            'status' => 'pendente',
            'total_linhas' => 10,
            'linhas_criadas' => 0,
            'linhas_atualizadas' => 0,
            'linhas_erro' => 0,
            'erros' => [],
            'mapeamento_usado' => [],
            'user_id' => UserFactory::new()->active(),
            'iniciado_em' => now(),
            'finalizado_em' => null,
        ];
    }
}
