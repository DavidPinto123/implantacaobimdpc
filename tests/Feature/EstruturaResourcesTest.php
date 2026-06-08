<?php

use App\Filament\Resources\AmbientesResource;
use App\Filament\Resources\DepartamentosResource;
use App\Models\Ambientes;
use App\Models\Departamentos;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('cobre CRUD básico da DepartamentosResource com foco no que o resource realmente oferece', function () {
    $user = createPrioritariosUserWithPermissions(['ViewAny:Departamentos', 'Create:Departamentos', 'Update:Departamentos']);
    $this->actingAs($user);

    $departamento = Departamentos::create([
        'nova_sigla' => 'NS-01',
        'unidade' => 'Unidade DCT',
        'departamento' => 'Comercial',
        'area' => '100',
        'data_extracao' => now()->toDateString(),
    ]);

    $departamento->update(['departamento' => 'Comercial Atualizado']);

    $this->get(DepartamentosResource::getUrl('index'))->assertOk();
    $this->get(DepartamentosResource::getUrl('create'))->assertOk();

    $this->assertDatabaseHas('departamentos', ['id' => $departamento->id, 'departamento' => 'Comercial Atualizado']);
});

it('cobre CRUD básico da AmbientesResource com foco no que o resource realmente oferece', function () {
    $user = createPrioritariosUserWithPermissions(['ViewAny:Ambientes', 'Create:Ambientes', 'Update:Ambientes']);
    $this->actingAs($user);

    $ambiente = Ambientes::create([
        'nova_sigla' => 'AMB-01',
        'unidade' => 'Unidade DCT',
        'marca' => 'Smart Fit',
        'departamento' => 'Recepção',
        'ambiente' => 'Hall',
        'area' => '50',
        'pavimento' => 'Térreo',
        'data_extracao' => now()->toDateString(),
    ]);

    $ambiente->update(['ambiente' => 'Hall Atualizado']);

    $this->get(AmbientesResource::getUrl('index'))->assertOk();
    $this->get(AmbientesResource::getUrl('create'))->assertOk();

    $this->assertDatabaseHas('ambientes', ['id' => $ambiente->id, 'ambiente' => 'Hall Atualizado']);
});
