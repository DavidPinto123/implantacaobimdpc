<?php

namespace Database\Factories;

use App\Models\OrdemInvestimento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrdemInvestimento>
 */
class OrdemInvestimentoFactory extends Factory
{
    protected $model = OrdemInvestimento::class;

    public function definition(): array
    {
        return [
            'projeto_id' => ProjetoFactory::new(),
            'valor_total' => 100000,
            'area' => 500,
            'custo_m2' => 200,
            'estrutura' => [],
            'pdf_path' => null,
            'user_id' => UserFactory::new()->active(),
        ];
    }
}
