<?php

use App\Filament\Resources\ProjetoResource;
use App\Models\Cidade;
use App\Models\Estado;
use App\Models\Etapa;
use App\Models\Pais;
use App\Models\Projeto;
use App\Models\Setor;
use App\Models\User;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function setupAdminPanelForTests(): void
{
    Filament::setCurrentPanel(Filament::getPanel('admin'));
}

function ensureDefaultRoles(): void
{
    foreach (['Planejamento Estratégico', 'PMO', 'Comercial', 'super_admin', 'Arquitetura', 'Engenharia', 'Gestor'] as $roleName) {
        Role::findOrCreate($roleName, 'web');
    }
}

function createActiveUserWithPermissions(array $permissions = [], array $attributes = []): User
{
    $user = User::factory()->active()->create($attributes);

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    if ($permissions !== []) {
        $user->givePermissionTo($permissions);
    }

    return $user;
}

function createLocationDependencies(string $countryName = 'Brasil'): array
{
    $pais = Pais::firstOrCreate(['nome' => $countryName]);

    $estado = Estado::firstOrCreate(
        ['pais_id' => $pais->id, 'uf' => 'SP'],
        ['nome' => 'São Paulo'],
    );

    $cidade = Cidade::firstOrCreate([
        'estado_id' => $estado->id,
        'nome' => 'São Paulo',
    ]);

    return compact('pais', 'estado', 'cidade');
}

function createDefaultSetor(string $name = 'Operações Teste'): Setor
{
    return Setor::firstOrCreate(['setor' => $name]);
}

function createProjetoDependencies(): array
{
    ['pais' => $pais, 'estado' => $estado, 'cidade' => $cidade] = createLocationDependencies();
    $etapa = Etapa::firstOrCreate(['nome' => 'Prospecção']);

    return compact('pais', 'estado', 'cidade', 'etapa');
}

function createProjetoRecord(User $user, array $overrides = []): Projeto
{
    ['pais' => $pais, 'estado' => $estado, 'cidade' => $cidade, 'etapa' => $etapa] = createProjetoDependencies();

    return Projeto::create(array_merge([
        'nome' => 'Projeto Teste '.str()->random(6),
        'sigla' => 'PRJ',
        'status' => 'Em processo',
        'user_id' => $user->id,
        'etapa_id' => $etapa->id,
        'cidade_id' => $cidade->id,
        'estado_id' => $estado->id,
        'pais_id' => $pais->id,
        'rua' => 'Rua Teste',
        'bairro' => 'Centro',
        'cep' => '01000-000',
        'numero' => '123',
    ], $overrides));
}

function projetoResourceUrl(string $page, array $parameters = []): string
{
    return ProjetoResource::getUrl($page, $parameters);
}
