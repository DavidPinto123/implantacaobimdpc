<?php

namespace Database\Factories;

use App\Models\RelatorioVisitaTecnica;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RelatorioVisitaTecnica>
 */
class RelatorioVisitaTecnicaFactory extends Factory
{
    protected $model = RelatorioVisitaTecnica::class;

    public function definition(): array
    {
        return [
            'projeto_id' => ProjetoFactory::new(),
            'numero_relatorio_vt' => 'VT-'.strtoupper(fake()->bothify('??####')),
            'autor' => fake()->name(),
            'status' => 'rascunho',
            'iniciado_em' => now(),
        ];
    }
}
