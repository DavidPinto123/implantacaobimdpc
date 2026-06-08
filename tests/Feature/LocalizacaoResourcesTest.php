<?php

use App\Filament\Resources\CidadeResource;
use App\Filament\Resources\EstadoResource;
use App\Filament\Resources\PaisResource;
use App\Models\Cidade;
use App\Models\Estado;
use App\Models\Pais;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('cobre CRUD básico da PaisResource com persistência por modelo e páginas principais', function () {
    $user = createPrioritariosUserWithPermissions(['ViewAny:Pais', 'Create:Pais', 'Update:Pais', 'View:Pais']);
    $this->actingAs($user);

    $pais = Pais::create(['nome' => 'Brasil DCT']);
    $pais->update(['nome' => 'Brasil DCT Atualizado']);

    $this->get(PaisResource::getUrl('index'))->assertOk();
    $this->get(PaisResource::getUrl('create'))->assertOk();
    $this->get(PaisResource::getUrl('edit', ['record' => $pais]))->assertOk();

    $this->assertDatabaseHas('pais', ['id' => $pais->id, 'nome' => 'Brasil DCT Atualizado']);
});

it('cobre CRUD básico da EstadoResource com persistência por modelo e páginas principais', function () {
    $user = createPrioritariosUserWithPermissions(['ViewAny:Estado', 'Create:Estado', 'Update:Estado', 'View:Estado']);
    $this->actingAs($user);

    $pais = Pais::create(['nome' => 'Pais Estado DCT']);
    $estado = Estado::create(['nome' => 'Estado Inicial', 'uf' => 'EI', 'pais_id' => $pais->id]);
    $estado->update(['nome' => 'Estado Atualizado']);

    $this->get(EstadoResource::getUrl('index'))->assertOk();
    $this->get(EstadoResource::getUrl('create'))->assertOk();
    $this->get(EstadoResource::getUrl('edit', ['record' => $estado]))->assertOk();

    $this->assertDatabaseHas('estados', ['id' => $estado->id, 'nome' => 'Estado Atualizado']);
});

it('cobre CRUD básico da CidadeResource com persistência por modelo e páginas principais', function () {
    $user = createPrioritariosUserWithPermissions(['ViewAny:Cidade', 'Create:Cidade', 'Update:Cidade', 'View:Cidade']);
    $this->actingAs($user);

    $pais = Pais::create(['nome' => 'Pais Cidade DCT']);
    $estado = Estado::create(['nome' => 'Estado Cidade', 'uf' => 'EC', 'pais_id' => $pais->id]);
    $cidade = Cidade::create(['nome' => 'Cidade Inicial', 'estado_id' => $estado->id]);
    $cidade->update(['nome' => 'Cidade Atualizada']);

    $this->get(CidadeResource::getUrl('index'))->assertOk();
    $this->get(CidadeResource::getUrl('create'))->assertOk();
    $this->get(CidadeResource::getUrl('edit', ['record' => $cidade]))->assertOk();

    $this->assertDatabaseHas('cidades', ['id' => $cidade->id, 'nome' => 'Cidade Atualizada']);
});
