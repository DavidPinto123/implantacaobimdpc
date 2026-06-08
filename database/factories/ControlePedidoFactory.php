<?php

namespace Database\Factories;

use App\Models\ControlePedido;
use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\Permission\Models\Role;

/**
 * @extends Factory<ControlePedido>
 */
class ControlePedidoFactory extends Factory
{
    protected $model = ControlePedido::class;

    public function definition(): array
    {
        $responsavelOrc = UserFactory::new()->active()->create();
        $responsavelOrc->assignRole(Role::findOrCreate('colaborador_orcamento', 'web'));

        $gestorObra = UserFactory::new()->active()->create();
        $gestorObra->assignRole(Role::findOrCreate('engenharia', 'web'));

        return [
            'projeto_id' => ProjetoFactory::new(),
            'construtora_id' => ConstrutoraFactory::new(),
            'elaboracao_contrato' => fake()->date(),
            'cnpj' => fake()->numerify('##############'),
            'status' => 'definitivo',
            'contratacao' => fake()->date(),
            'observacoes' => fake()->sentence(),
            'valor_oi' => 100000,
            'valor_realizado' => 25000,
            'realizado_nf' => 10000,
            'saldo' => 75000,
            'situacao' => 'em_processo',
            'responsavel_orc' => (string) $responsavelOrc->id,
            'gestor_obra' => (string) $gestorObra->id,
            'numero' => fake()->numberBetween(1, 999),
            'pedidos' => [],
        ];
    }
}
