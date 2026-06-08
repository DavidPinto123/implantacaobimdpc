<?php

use App\Filament\Resources\CidadeResource;
use App\Filament\Resources\EstadoResource;
use App\Filament\Resources\PaisResource;
use App\Models\Cidade;
use App\Models\Estado;
use App\Models\Pais;
use Filament\Facades\Filament;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('browser smoke da PaisResource navega entre list/create/edit', function () {
    $user = createPrioritariosUserWithPermissions(['ViewAny:Pais', 'Create:Pais', 'Update:Pais']);
    $this->actingAs($user);

    $pais = Pais::create(['nome' => 'Pais Browser']);

    visit(PaisResource::getUrl('index'))->assertPathIs('/admin/paises');
    visit(PaisResource::getUrl('create'))->assertPathIs('/admin/paises/create');
    visit(PaisResource::getUrl('edit', ['record' => $pais]))->assertPathIs("/admin/paises/{$pais->id}/edit");
});

it('browser smoke da EstadoResource navega entre list/create/edit', function () {
    $user = createPrioritariosUserWithPermissions(['ViewAny:Estado', 'Create:Estado', 'Update:Estado']);
    $this->actingAs($user);

    $pais = Pais::create(['nome' => 'Pais Estado Browser']);
    $estado = Estado::create(['nome' => 'Estado Browser', 'uf' => 'EB', 'pais_id' => $pais->id]);

    visit(EstadoResource::getUrl('index'))->assertPathIs('/admin/estados');
    visit(EstadoResource::getUrl('create'))->assertPathIs('/admin/estados/create');
    visit(EstadoResource::getUrl('edit', ['record' => $estado]))->assertPathIs("/admin/estados/{$estado->id}/edit");
});

it('browser smoke da CidadeResource navega entre list/create/edit', function () {
    $user = createPrioritariosUserWithPermissions(['ViewAny:Cidade', 'Create:Cidade', 'Update:Cidade']);
    $this->actingAs($user);

    $pais = Pais::create(['nome' => 'Pais Cidade Browser']);
    $estado = Estado::create(['nome' => 'Estado Cidade Browser', 'uf' => 'CB', 'pais_id' => $pais->id]);
    $cidade = Cidade::create(['nome' => 'Cidade Browser', 'estado_id' => $estado->id]);

    visit(CidadeResource::getUrl('index'))->assertPathIs('/admin/cidades');
    visit(CidadeResource::getUrl('create'))->assertPathIs('/admin/cidades/create');
    visit(CidadeResource::getUrl('edit', ['record' => $cidade]))->assertPathIs("/admin/cidades/{$cidade->id}/edit");
});
