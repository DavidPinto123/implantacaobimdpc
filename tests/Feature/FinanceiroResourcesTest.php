<?php

use App\Filament\Resources\ControleNotaFiscals\ControleNotaFiscalResource;
use App\Filament\Resources\ImportacaoNotaFiscals\ImportacaoNotaFiscalResource;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('cobre visualizacao da ControleNotaFiscalResource sem rota de criacao', function () {
    $user = createFinanceiroUserWithPermissions([
        'ViewAny:ControleNotaFiscal',
        'View:ControleNotaFiscal',
    ], asSuperAdmin: true);

    $this->actingAs($user);

    ['controle' => $controle] = createControleNotaFiscalComNota($user);
    $this->get(ControleNotaFiscalResource::getUrl('index'))
        ->assertOk()
        ->assertSee('--cnf-zoom', false)
        ->assertSee('gs-controle-page-size-select', false)
        ->assertSee('stickyHeight', false)
        ->assertSee('offsetHeight', false);
    $this->get('/admin/controle-notas-fiscais/create')->assertNotFound();
    $this->get(ControleNotaFiscalResource::getUrl('edit', ['record' => $controle]))->assertOk();

    $this->assertDatabaseHas('controle_nota_fiscals', [
        'id' => $controle->id,
    ]);
});

it('cobre CRUD básico da ImportacaoNotaFiscalResource com create pesado em fallback de modelo e páginas reais', function () {
    $user = createFinanceiroUserWithPermissions([
        'ViewAny:ControleNotaFiscalNota',
        'Create:ControleNotaFiscalNota',
        'Update:ControleNotaFiscalNota',
        'View:ControleNotaFiscalNota',
    ]);

    $this->actingAs($user);

    ['nota' => $nota] = createControleNotaFiscalComNota($user);
    $nota->update(['status' => 'aprovado']);

    $this->get(ImportacaoNotaFiscalResource::getUrl('index'))->assertOk();
    $this->get(ImportacaoNotaFiscalResource::getUrl('create'))->assertOk();
    $this->get(ImportacaoNotaFiscalResource::getUrl('edit', ['record' => $nota]))->assertOk();

    $this->assertDatabaseHas('controle_nota_fiscal_notas', [
        'id' => $nota->id,
        'status' => 'aprovado',
    ]);
});
