<?php

use App\Filament\Resources\TaskCategories\TaskCategoryResource;
use App\Models\TaskCategory;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

beforeEach(function () {
    setupAdminPanelForTests();
    ensureDefaultRoles();
});

it('cobre CRUD básico da TaskCategoryResource com role autorizada na navegação', function () {
    $user = createTaskManagementUser([
        'ViewAny:TaskCategory',
        'View:TaskCategory',
        'Create:TaskCategory',
        'Update:TaskCategory',
        'Delete:TaskCategory',
    ], 'Gestor');

    $this->actingAs($user);

    $categoria = TaskCategory::create(['name' => 'Categoria Inicial DCT-009']);

    $this->get(TaskCategoryResource::getUrl('index'))->assertOk();
    $this->get(TaskCategoryResource::getUrl('create'))->assertOk();
    $this->get(TaskCategoryResource::getUrl('view', ['record' => $categoria]))->assertOk();
    $this->get(TaskCategoryResource::getUrl('edit', ['record' => $categoria]))->assertOk();

    $categoria->update(['name' => 'Categoria Atualizada DCT-009']);
    $this->assertDatabaseHas('task_categories', ['id' => $categoria->id, 'name' => 'Categoria Atualizada DCT-009']);

    $categoria->delete();
    $this->assertDatabaseMissing('task_categories', ['id' => $categoria->id]);
});
