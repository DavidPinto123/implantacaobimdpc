<?php

use App\Filament\Resources\Tasks\TaskResource;
use App\Models\Marca;
use App\Models\Task;
use App\Models\TaskCategory;

beforeEach(function () {
    setupAdminPanelForTests();
    ensureDefaultRoles();
});

it('smoke de navegação da TaskResource entre list/view/edit', function () {
    $user = createTaskManagementUser(['ViewAny:Task', 'View:Task', 'Update:Task', 'Create:Task'], 'Coordenador');
    $this->actingAs($user);

    $setor = attachUserToSetor($user, 'Setor Browser Tarefas');
    $categoria = TaskCategory::create(['name' => 'Categoria Browser Tarefas']);
    $marca = Marca::create(['nome' => 'Marca Browser Tarefas']);

    $task = Task::create([
        'title' => 'Tarefa Browser DCT-009',
        'description' => 'Smoke test de navegação',
        'task_category_id' => $categoria->id,
        'sigla' => 'BRW009',
        'marca_id' => $marca->id,
        'setor_id' => $setor->id,
        'assigned_to' => $user->id,
        'inicio' => now()->toDateString(),
        'prazo' => 1,
        'dias_corridos' => true,
        'status' => 'pendente',
    ]);

    $indexUrl = TaskResource::getUrl('index');
    $viewUrl = TaskResource::getUrl('view', ['record' => $task]);
    $editUrl = TaskResource::getUrl('edit', ['record' => $task]);

    visit($indexUrl)
        ->assertPathIs(parse_url($indexUrl, PHP_URL_PATH));

    visit($viewUrl)
        ->assertPathIs(parse_url($viewUrl, PHP_URL_PATH));

    visit($editUrl)
        ->assertPathIs(parse_url($editUrl, PHP_URL_PATH));
});
