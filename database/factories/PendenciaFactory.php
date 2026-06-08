<?php

namespace Database\Factories;

use App\Models\PosObra\Pendencia;
use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\Permission\Models\Role;

/**
 * @extends Factory<Pendencia>
 */
class PendenciaFactory extends Factory
{
    protected $model = Pendencia::class;

    public function definition(): array
    {
        $gestor = UserFactory::new()->active()->create();
        $gestor->assignRole(Role::findOrCreate('super_admin', 'web'));

        $lider = UserFactory::new()->active()->create(['is_lider_obra' => true]);

        return [
            'codigo' => 'PO-'.now()->format('Y').'-'.fake()->unique()->numerify('####'),
            'obras_id' => ObrasFactory::new(),
            'construtora_id' => ConstrutoraFactory::new(),
            'lider_obra_id' => $lider->id,
            'gestor_id' => $gestor->id,
            'disciplina_config_id' => DisciplinaConfigFactory::new(),
            'ticket' => fake()->bothify('TKT-#####'),
            'descricao' => fake()->sentence(),
            'observacoes' => fake()->sentence(),
            'urgencia' => 'P2',
            'status' => 'REGISTRADA',
            'data_inicio' => fake()->date(),
            'data_termino' => now()->addDays(2)->toDateString(),
            'impacto_operacao' => false,
            'local_especifico' => fake()->word(),
        ];
    }
}
