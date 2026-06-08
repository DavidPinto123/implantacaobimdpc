<?php

use App\Filament\Resources\TaskCategories\TaskCategoryResource;
use App\Models\TaskCategory;

beforeEach(function () {
    setupAdminPanelForTests();
    ensureDefaultRoles();
});

it('smoke de navegação da TaskCategoryResource entre list/create/edit', function () {
    $user = createTaskManagementUser([
        'ViewAny:TaskCategory',
        'View:TaskCategory',
        'Create:TaskCategory',
        'Update:TaskCategory',
    ], 'Diretor');

    $this->actingAs($user);

    $categoria = TaskCategory::create(['name' => 'Categoria Browser DCT-009']);

    $indexUrl = TaskCategoryResource::getUrl('index');
    $createUrl = TaskCategoryResource::getUrl('create');
    $editUrl = TaskCategoryResource::getUrl('edit', ['record' => $categoria]);

    visit($indexUrl)
        ->assertPathIs(parse_url($indexUrl, PHP_URL_PATH));

    visit($createUrl)
        ->assertPathIs(parse_url($createUrl, PHP_URL_PATH));

    visit($editUrl)
        ->assertPathIs(parse_url($editUrl, PHP_URL_PATH));
});
