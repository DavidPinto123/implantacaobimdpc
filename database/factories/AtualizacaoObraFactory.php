<?php

namespace Database\Factories;

use App\Enums\CategoriaAtualizacaoObra;
use App\Models\AtualizacaoObra;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AtualizacaoObra>
 */
class AtualizacaoObraFactory extends Factory
{
    protected $model = AtualizacaoObra::class;

    public function definition(): array
    {
        return [
            'obra_id' => ObrasFactory::new(),
            'usuario_id' => UserFactory::new()->active(),
            'parent_id' => null,
            'categoria' => CategoriaAtualizacaoObra::GERAL,
            'titulo' => fake()->sentence(4),
            'conteudo' => fake()->paragraph(),
            'mencoes' => null,
            'campo_alterado' => null,
            'valor_anterior' => null,
            'valor_novo' => null,
            'fixado' => false,
            'automatico' => true,
        ];
    }
}
