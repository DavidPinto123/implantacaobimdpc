<?php

use App\Filament\Resources\Tasks\Pages\ListTasks;
use App\Filament\Resources\Tasks\TaskResource;
use App\Models\Marca;
use App\Models\Task;
use App\Models\TaskCategory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

beforeEach(function () {
    setupAdminPanelForTests();
    ensureDefaultRoles();
});

it('cobre CRUD básico da TaskResource no fluxo real (list/view/edit, sem rota create)', function () {
    $user = createTaskManagementUser(['ViewAny:Task', 'View:Task', 'Update:Task', 'Create:Task', 'Delete:Task']);
    $this->actingAs($user);

    $setorPermitido = attachUserToSetor($user, 'Setor Permitido DCT-009');
    $setorNaoPermitido = attachUserToSetor(createTaskManagementUser([], 'Colaborador'), 'Setor Restrito DCT-009');

    $categoria = TaskCategory::create(['name' => 'Categoria DCT-009']);
    $marca = Marca::create(['nome' => 'Marca DCT-009']);

    $responsavel = createTaskManagementUser([], 'Colaborador');
    $responsavel->setores()->syncWithoutDetaching([$setorPermitido->id]);

    $taskVisivel = Task::create([
        'title' => 'Tarefa visível DCT-009',
        'description' => 'Descrição inicial',
        'task_category_id' => $categoria->id,
        'sigla' => 'DCT009',
        'marca_id' => $marca->id,
        'setor_id' => $setorPermitido->id,
        'assigned_to' => $responsavel->id,
        'inicio' => now()->toDateString(),
        'prazo' => 2,
        'dias_corridos' => true,
        'status' => 'pendente',
    ]);

    $taskNaoVisivel = Task::create([
        'title' => 'Tarefa fora do setor',
        'description' => 'Não deve aparecer para o usuário',
        'task_category_id' => $categoria->id,
        'sigla' => 'DCT009X',
        'marca_id' => $marca->id,
        'setor_id' => $setorNaoPermitido->id,
        'assigned_to' => $responsavel->id,
        'inicio' => now()->toDateString(),
        'prazo' => 3,
        'dias_corridos' => true,
        'status' => 'pendente',
    ]);

    $taskVisivel->update([
        'title' => 'Tarefa visível DCT-009 (atualizada)',
        'status' => 'em_andamento',
    ]);

    $this->get(TaskResource::getUrl('index'))->assertOk();
    $this->get(TaskResource::getUrl('view', ['record' => $taskVisivel]))->assertOk();
    $this->get(TaskResource::getUrl('edit', ['record' => $taskVisivel]))->assertOk();

    Livewire::test(ListTasks::class)
        ->assertCanSeeTableRecords([$taskVisivel])
        ->assertCanNotSeeTableRecords([$taskNaoVisivel]);

    $this->assertDatabaseHas('tasks', [
        'id' => $taskVisivel->id,
        'title' => 'Tarefa visível DCT-009 (atualizada)',
        'status' => 'em_andamento',
    ]);
});
