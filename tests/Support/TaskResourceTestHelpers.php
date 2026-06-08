<?php

use App\Models\Setor;
use App\Models\User;
use Spatie\Permission\Models\Role;

function createTaskManagementUser(array $permissions, string $role = 'Coordenador'): User
{
    $user = createActiveUserWithPermissions($permissions);

    $user->assignRole(Role::findOrCreate($role, 'web'));

    return $user;
}

function attachUserToSetor(User $user, ?string $name = null): Setor
{
    $setor = Setor::create([
        'setor' => $name ?? 'Setor Tarefas '.str()->random(5),
    ]);

    $user->setores()->syncWithoutDetaching([$setor->id]);

    return $setor;
}
