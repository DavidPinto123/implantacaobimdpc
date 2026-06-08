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
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('cobre CRUD básico da EmpresasResource com persistência por modelo e páginas principais', function () {
    $user = createPrioritariosUserWithPermissions(['ViewAny:Empresas', 'Create:Empresas', 'Update:Empresas', 'View:Empresas']);
    $this->actingAs($user);

    ['pais' => $pais, 'estado' => $estado, 'cidade' => $cidade] = createPrioritariosGeoDependencies();

    $empresa = Empresas::create([
        'nome' => 'Empresa DCT',
        'nome_fantasia' => 'Empresa Teste',
        'responsavel' => 'Responsável DCT',
        'email' => 'empresa.dct@example.com',
        'contato' => '11999999999',
        'cnpj' => '12345678000195',
        'tipo' => 'Fornecedor',
        'status' => true,
        'pais_id' => $pais->id,
        'estado_id' => $estado->id,
        'cidade_id' => $cidade->id,
    ]);

    $empresa->update(['nome_fantasia' => 'Empresa Teste Atualizada']);

    $this->get(EmpresasResource::getUrl('index'))->assertOk();
    $this->get(EmpresasResource::getUrl('create'))->assertOk();
    $this->get(EmpresasResource::getUrl('edit', ['record' => $empresa]))->assertOk();

    $this->assertDatabaseHas('empresas', ['id' => $empresa->id, 'nome_fantasia' => 'Empresa Teste Atualizada']);
});

it('cobre CRUD básico da ConstrutoraResource com persistência por modelo e páginas principais', function () {
    $user = createPrioritariosUserWithPermissions(['ViewAny:Construtora', 'Create:Construtora', 'Update:Construtora']);
    $this->actingAs($user);

    $construtora = Construtora::create([
        'nome' => 'Fornecedor DCT',
        'cnpj' => '11111111000191',
        'tipo' => 'CONSTRUTORA',
    ]);

    $construtora->update(['nome' => 'Fornecedor DCT Atualizada']);

    $this->get(ConstrutoraResource::getUrl('index'))->assertOk();
    $this->get(ConstrutoraResource::getUrl('create'))->assertOk();
    $this->get(ConstrutoraResource::getUrl('edit', ['record' => $construtora]))->assertOk();

    $this->assertDatabaseHas('construtoras', ['id' => $construtora->id, 'nome' => 'Fornecedor DCT Atualizada']);
});

it('cobre CRUD básico da SetorResource com persistência por modelo e páginas principais', function () {
    $user = createPrioritariosUserWithPermissions(['ViewAny:Setor', 'Create:Setor', 'Update:Setor']);
    $this->actingAs($user);

    $setor = Setor::create(['setor' => 'Setor Inicial']);
    $setor->update(['setor' => 'Setor Atualizado']);

    $this->get(SetorResource::getUrl('index'))->assertOk();
    $this->get(SetorResource::getUrl('create'))->assertOk();
    $this->get(SetorResource::getUrl('edit', ['record' => $setor]))->assertOk();

    $this->assertDatabaseHas('setores', ['id' => $setor->id, 'setor' => 'Setor Atualizado']);
});

it('cobre CRUD básico da MarcaResource com persistência por modelo e páginas principais', function () {
    $user = createPrioritariosUserWithPermissions(['ViewAny:Marca', 'Create:Marca', 'Update:Marca', 'View:Marca']);
    $this->actingAs($user);

    $marca = Marca::create(['nome' => 'Marca Inicial']);
    $marca->update(['nome' => 'Marca Atualizada']);

    $this->get(MarcaResource::getUrl('index'))->assertOk();
    $this->get(MarcaResource::getUrl('create'))->assertOk();
    $this->get(MarcaResource::getUrl('edit', ['record' => $marca]))->assertOk();

    $this->assertDatabaseHas('marcas', ['id' => $marca->id, 'nome' => 'Marca Atualizada']);
});

it('cobre CRUD básico da EtapaResource com persistência por modelo e páginas principais', function () {
    $user = createPrioritariosUserWithPermissions(['ViewAny:Etapa', 'Create:Etapa', 'Update:Etapa', 'View:Etapa']);
    $this->actingAs($user);

    $etapa = Etapa::create(['nome' => 'Etapa Inicial']);
    $etapa->update(['nome' => 'Etapa Atualizada']);

    $this->get(EtapaResource::getUrl('index'))->assertOk();
    $this->get(EtapaResource::getUrl('create'))->assertOk();
    $this->get(EtapaResource::getUrl('edit', ['record' => $etapa]))->assertOk();

    $this->assertDatabaseHas('etapas', ['id' => $etapa->id, 'nome' => 'Etapa Atualizada']);
});
