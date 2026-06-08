<?php

namespace Database\Factories;

use App\Models\Marca;
use App\Models\Projeto;
use App\Models\Setor;
use App\Models\Task;
use App\Models\TaskCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        $createdBy = User::factory()->active();

        return [
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'task_category_id' => TaskCategory::factory(),
            'sigla' => strtoupper(fake()->bothify('??###')),
            'marca_id' => Marca::query()->firstOrCreate(['nome' => 'Marca Factory'])->id,
            'created_by' => $createdBy,
            'assigned_to' => User::factory()->active(),
            'prazo' => fake()->numberBetween(1, 30),
            'inicio' => now()->toDateString(),
            'termino_programado' => null,
            'data_entrega' => null,
            'status' => 'pendente',
            'setor_id' => Setor::factory(),
            'dias_corridos' => true,
            'projeto_id' => Projeto::factory(),
        ];
    }
}
