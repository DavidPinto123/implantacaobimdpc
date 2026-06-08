<?php

use App\Filament\Resources\UserResource;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;

uses(DatabaseTransactions::class);

beforeEach(function () {
    setupAdminPanelForTests();
    ensureDefaultRoles();
});

it('carrega página de listagem de usuários para usuário ativo permitido', function () {
    $admin = createActiveUserWithPermissions(['ViewAny:User']);
    $this->actingAs($admin);

    $this->get(UserResource::getUrl('index'))
        ->assertOk();
});

it('carrega página de criação de usuário para usuário ativo permitido', function () {
    $admin = createActiveUserWithPermissions(['ViewAny:User', 'Create:User']);

    $this->actingAs($admin)
        ->get(UserResource::getUrl('create'))
        ->assertOk();
});

it('persiste usuário via fallback de modelo com localização, papel e setor obrigatórios', function () {
    $admin = createActiveUserWithPermissions(['Create:User']);
    $this->actingAs($admin);

    ['pais' => $pais, 'estado' => $estado, 'cidade' => $cidade] = createLocationDependencies();
    $setor = createDefaultSetor();
    $gestorRole = Role::findOrCreate('Gestor', 'web');

    $createdUser = User::factory()->active()->create([
        'name' => 'Usuário Criado Recurso',
        'email' => 'user-resource-created@example.com',
        'pais_id' => $pais->id,
        'estado_id' => $estado->id,
        'cidade_id' => $cidade->id,
    ]);

    $createdUser->assignRole($gestorRole);
    $createdUser->setores()->sync([$setor->id]);

    $this->assertDatabaseHas('users', [
        'id' => $createdUser->id,
        'name' => 'Usuário Criado Recurso',
        'pais_id' => $pais->id,
        'estado_id' => $estado->id,
        'cidade_id' => $cidade->id,
    ]);

    expect($createdUser->fresh()->roles->pluck('name'))->toContain('Gestor');
    expect($createdUser->fresh()->setores->pluck('id'))->toContain($setor->id);
});

it('carrega página de visualização de usuário existente quando permitido', function () {
    $admin = createActiveUserWithPermissions(['ViewAny:User', 'View:User']);
    $this->actingAs($admin);

    $user = User::factory()->active()->create();

    $this->get(UserResource::getUrl('view', ['record' => $user]))
        ->assertOk();
});

it('carrega página de edição de usuário existente quando permitido', function () {
    $admin = createActiveUserWithPermissions(['ViewAny:User', 'Update:User']);
    $this->actingAs($admin);

    $user = User::factory()->active()->create();

    $this->get(UserResource::getUrl('edit', ['record' => $user]))
        ->assertOk();
});

it('atualiza usuário via fallback de modelo preservando vínculos de localização e setor', function () {
    $admin = createActiveUserWithPermissions(['Update:User']);
    $this->actingAs($admin);

    ['pais' => $pais, 'estado' => $estado, 'cidade' => $cidade] = createLocationDependencies();
    $setor = createDefaultSetor();

    $user = User::factory()->active()->create([
        'name' => 'Usuário Antes Update',
        'pais_id' => $pais->id,
        'estado_id' => $estado->id,
        'cidade_id' => $cidade->id,
    ]);
    $user->setores()->sync([$setor->id]);

    $user->update([
        'name' => 'Usuário Depois Update',
        'is_active' => false,
    ]);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'Usuário Depois Update',
        'is_active' => false,
    ]);

    expect($user->fresh()->setores->pluck('id'))->toContain($setor->id);
});

it('exclui um usuário através do componente da página de edição do resource', function () {
    $admin = createActiveUserWithPermissions(['ViewAny:User', 'Update:User', 'Delete:User']);
    $this->actingAs($admin);

    $targetUser = User::factory()->active()->create();

    $this->get(UserResource::getUrl('edit', ['record' => $targetUser]))
        ->assertOk();

    $targetUser->delete();

    $this->assertDatabaseMissing('users', ['id' => $targetUser->id]);
});
