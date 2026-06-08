<?php

use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(DatabaseTransactions::class);

beforeEach(function () {
    setupFilamentResourceCoverageForTests($this);
});

it('valida visibilidade e regras principais no CreateUser', function () {
    ['pais' => $pais, 'estado' => $estado, 'cidade' => $cidade] = createLocationDependencies('Brasil Cobertura');
    $setor = createDefaultSetor('Setor Cobertura');
    $role = Role::findOrCreate('Gestor', 'web');
    User::factory()->active()->create(['email' => 'duplicado-user-resource@example.com']);

    Livewire::test(CreateUser::class)
        ->assertFormFieldVisible('name')
        ->assertFormFieldVisible('email')
        ->assertFormFieldVisible('pais_id')
        ->assertFormFieldVisible('estado_id')
        ->assertFormFieldVisible('cidade_id')
        ->assertFormFieldVisible('roles')
        ->assertFormFieldVisible('setores')
        ->fillForm([
            'name' => '',
            'email' => 'duplicado-user-resource@example.com',
            'pais_id' => $pais->id,
            'estado_id' => $estado->id,
            'cidade_id' => $cidade->id,
            'roles' => [$role->id],
            'setores' => [$setor->id],
        ])
        ->call('create')
        ->assertHasFormErrors([
            'name' => 'required',
            'email' => 'unique',
        ]);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Novo Usuário Cobertura',
            'email' => 'invalido-email',
            'pais_id' => null,
            'estado_id' => null,
            'cidade_id' => null,
            'roles' => [],
            'setores' => [],
        ])
        ->call('create')
        ->assertHasFormErrors([
            'email' => 'email',
            'pais_id' => 'required',
            'estado_id' => 'required',
            'cidade_id' => 'required',
            'roles' => 'required',
            'setores' => 'required',
        ]);
});
