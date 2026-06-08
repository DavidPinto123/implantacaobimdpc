<?php

use App\Models\Cidade;
use App\Models\Estado;
use App\Models\Etapa;
use App\Models\Pais;
use App\Models\Projeto;
use App\Models\User;
use Spatie\Permission\Models\Permission;

function createPrioritariosUserWithPermissions(array $permissions): User
{
    $user = User::factory()->active()->create();

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user->givePermissionTo($permissions);

    return $user;
}

function createPrioritariosGeoDependencies(): array
{
    $pais = Pais::create(['nome' => 'Brasil '.str()->random(5)]);
    $estado = Estado::create(['nome' => 'São Paulo '.str()->random(4), 'uf' => strtoupper(str()->random(2)), 'pais_id' => $pais->id]);
    $cidade = Cidade::create(['nome' => 'Cidade '.str()->random(5), 'estado_id' => $estado->id]);

    return compact('pais', 'estado', 'cidade');
}

function createPrioritariosProjeto(User $user): Projeto
{
    ['pais' => $pais, 'estado' => $estado, 'cidade' => $cidade] = createPrioritariosGeoDependencies();
    $etapa = Etapa::create(['nome' => 'Etapa '.str()->random(4)]);

    return Projeto::create([
        'nome' => 'Projeto Relatório '.str()->random(5),
        'sigla' => strtoupper(str()->random(3)),
        'status' => 'Em processo',
        'user_id' => $user->id,
        'etapa_id' => $etapa->id,
        'cidade_id' => $cidade->id,
        'estado_id' => $estado->id,
        'pais_id' => $pais->id,
        'rua' => 'Rua Teste',
        'bairro' => 'Centro',
        'cep' => '01000-000',
        'numero' => '100',
    ]);
}
