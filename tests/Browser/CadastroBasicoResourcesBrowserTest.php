<?php

use App\Filament\Resources\ConstrutoraResource;
use App\Filament\Resources\EmpresasResource;
use App\Filament\Resources\EtapaResource;
use App\Filament\Resources\MarcaResource;
use App\Filament\Resources\SetorResource;
use App\Models\Construtora;
use App\Models\Empresas;
use App\Models\Etapa;
use App\Models\Marca;
use App\Models\Setor;
use Filament\Facades\Filament;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('browser smoke da EmpresasResource navega entre list/create/edit', function () {
    $user = createPrioritariosUserWithPermissions(['ViewAny:Empresas', 'Create:Empresas', 'Update:Empresas']);
    $this->actingAs($user);

    ['pais' => $pais, 'estado' => $estado, 'cidade' => $cidade] = createPrioritariosGeoDependencies();
    $empresa = Empresas::create([
        'nome' => 'Empresa Browser',
        'nome_fantasia' => 'Empresa Browser',
        'responsavel' => 'Responsável Browser',
        'email' => 'empresa.browser@example.com',
        'contato' => '11988887777',
        'cnpj' => '12345678000195',
        'tipo' => 'Fornecedor',
        'status' => true,
        'pais_id' => $pais->id,
        'estado_id' => $estado->id,
        'cidade_id' => $cidade->id,
    ]);

    visit(EmpresasResource::getUrl('index'))->assertPathIs('/admin/empresas');
    visit(EmpresasResource::getUrl('create'))->assertPathIs('/admin/empresas/create');
    visit(EmpresasResource::getUrl('edit', ['record' => $empresa]))->assertPathIs("/admin/empresas/{$empresa->id}/edit");
});

it('browser smoke da ConstrutoraResource navega entre list/create/edit', function () {
    $user = createPrioritariosUserWithPermissions(['ViewAny:Construtora', 'Create:Construtora', 'Update:Construtora']);
    $this->actingAs($user);

    $construtora = Construtora::create(['nome' => 'Fornecedor Browser', 'cnpj' => '11111111000191', 'tipo' => 'CONSTRUTORA']);

    visit(ConstrutoraResource::getUrl('index'))->assertPathIs('/admin/construtoras');
    visit(ConstrutoraResource::getUrl('create'))->assertPathIs('/admin/construtoras/create');
    visit(ConstrutoraResource::getUrl('edit', ['record' => $construtora]))->assertPathIs("/admin/construtoras/{$construtora->id}/edit");
});

it('browser smoke da SetorResource navega entre list/create/edit', function () {
    $user = createPrioritariosUserWithPermissions(['ViewAny:Setor', 'Create:Setor', 'Update:Setor']);
    $this->actingAs($user);

    $setor = Setor::create(['setor' => 'Setor Browser']);

    visit(SetorResource::getUrl('index'))->assertPathIs('/admin/setores');
    visit(SetorResource::getUrl('create'))->assertPathIs('/admin/setores/create');
    visit(SetorResource::getUrl('edit', ['record' => $setor]))->assertPathIs("/admin/setores/{$setor->id}/edit");
});

it('browser smoke da MarcaResource navega entre list/create/edit', function () {
    $user = createPrioritariosUserWithPermissions(['ViewAny:Marca', 'Create:Marca', 'Update:Marca']);
    $this->actingAs($user);

    $marca = Marca::create(['nome' => 'Marca Browser']);

    visit(MarcaResource::getUrl('index'))->assertPathIs('/admin/marcas');
    visit(MarcaResource::getUrl('create'))->assertPathIs('/admin/marcas/create');
    visit(MarcaResource::getUrl('edit', ['record' => $marca]))->assertPathIs("/admin/marcas/{$marca->id}/edit");
});

it('browser smoke da EtapaResource navega entre list/create/edit', function () {
    $user = createPrioritariosUserWithPermissions(['ViewAny:Etapa', 'Create:Etapa', 'Update:Etapa']);
    $this->actingAs($user);

    $etapa = Etapa::create(['nome' => 'Etapa Browser']);

    visit(EtapaResource::getUrl('index'))->assertPathIs('/admin/etapas');
    visit(EtapaResource::getUrl('create'))->assertPathIs('/admin/etapas/create');
    visit(EtapaResource::getUrl('edit', ['record' => $etapa]))->assertPathIs("/admin/etapas/{$etapa->id}/edit");
});
