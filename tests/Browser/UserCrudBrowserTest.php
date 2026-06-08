<?php

use App\Filament\Resources\UserResource;
use App\Models\User;

beforeEach(function () {
    ensureDefaultRoles();
});

it('acessa a lista de usuários em contexto admin autenticado', function () {
    $admin = createActiveUserWithPermissions(['ViewAny:User']);

    $this->actingAs($admin);

    User::factory()->active()->create(['name' => 'Usuário Browser Lista']);

    visit(UserResource::getUrl('index'))
        ->assertPathIs('/admin/usuarios')
        ->assertSee('Lista de Usuários')
        ->assertPresent('button[type="button"]');
});

it('cobre smoke de navegação de usuários no browser para páginas de criação e edição', function () {
    $admin = createActiveUserWithPermissions([
        'ViewAny:User',
        'Create:User',
        'Update:User',
    ]);

    $this->actingAs($admin);

    visit(UserResource::getUrl('create'))
        ->assertPathIs('/admin/usuarios/create')
        ->assertSee('Dados do Usuário')
        ->assertPresent('button[type="submit"]');

    $targetUser = User::factory()->active()->create(['name' => 'Usuário Browser CRUD']);

    visit(UserResource::getUrl('edit', ['record' => $targetUser]))
        ->assertPathIs("/admin/usuarios/{$targetUser->id}/edit")
        ->assertPresent('button[type="submit"]');
});
