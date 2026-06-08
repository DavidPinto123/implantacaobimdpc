<?php

use App\Filament\Resources\AmbientesResource;
use App\Filament\Resources\DepartamentosResource;
use App\Models\Ambientes;
use App\Models\Departamentos;
use Filament\Facades\Filament;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('browser smoke da DepartamentosResource navega entre list/create', function () {
    $user = createPrioritariosUserWithPermissions(['ViewAny:Departamentos', 'Create:Departamentos']);
    $this->actingAs($user);

    Departamentos::create([
        'nova_sigla' => 'NS-BR',
        'unidade' => 'Unidade Browser',
        'departamento' => 'TI',
        'area' => '40',
        'data_extracao' => now()->toDateString(),
    ]);

    visit(DepartamentosResource::getUrl('index'))->assertPathIs('/admin/departamentos');
    visit(DepartamentosResource::getUrl('create'))->assertPathIs('/admin/departamentos/create');
});

it('browser smoke da AmbientesResource navega entre list/create', function () {
    $user = createPrioritariosUserWithPermissions(['ViewAny:Ambientes', 'Create:Ambientes']);
    $this->actingAs($user);

    Ambientes::create([
        'nova_sigla' => 'AMB-BR',
        'unidade' => 'Unidade Browser',
        'marca' => 'Smart Fit',
        'departamento' => 'Musculação',
        'ambiente' => 'Salão',
        'area' => '80',
        'pavimento' => 'Térreo',
        'data_extracao' => now()->toDateString(),
    ]);

    visit(AmbientesResource::getUrl('index'))->assertPathIs('/admin/ambientes');
    visit(AmbientesResource::getUrl('create'))->assertPathIs('/admin/ambientes/create');
});
