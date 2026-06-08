<?php

namespace Database\Factories;

use App\Models\RelatorioFotografico;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RelatorioFotografico>
 */
class RelatorioFotograficoFactory extends Factory
{
    protected $model = RelatorioFotografico::class;

    public function definition(): array
    {
        return [
            'status_relatorio' => 'rascunho',
            'projeto_id' => ProjetoFactory::new(),
            'gestor_id' => UserFactory::new()->active(),
            'autor_id' => UserFactory::new()->active(),
            'status' => 'rascunho',
            'status_termo_de_posse' => null,
            'sigla' => strtoupper(fake()->bothify('??#')),
            'tipo_unidade' => fake()->randomElement(['Loja', 'Quiosque']),
            'endereco' => fake()->address(),
            'entregas_contratuais' => [],
            'fotos' => [],
        ];
    }
}
