<?php

use App\Filament\Resources\ControleNotaFiscals\ControleNotaFiscalResource;
use App\Filament\Resources\ImportacaoNotaFiscals\ImportacaoNotaFiscalResource;
use Filament\Facades\Filament;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('browser smoke da ControleNotaFiscalResource navega entre list/view sem rota de criacao', function () {
    $user = createFinanceiroUserWithPermissions([
        'ViewAny:ControleNotaFiscal',
        'View:ControleNotaFiscal',
    ], asSuperAdmin: true);

    $this->actingAs($user);

    ['controle' => $controle] = createControleNotaFiscalComNota($user);

    visit(ControleNotaFiscalResource::getUrl('index'))->assertPathIs('/admin/controle-notas-fiscais');
    visit(ControleNotaFiscalResource::getUrl('edit', ['record' => $controle]))->assertPathIs("/admin/controle-notas-fiscais/{$controle->id}");
});

it('browser smoke da ImportacaoNotaFiscalResource navega entre list/create/edit sem fluxo de upload', function () {
    $user = createFinanceiroUserWithPermissions([
        'ViewAny:ControleNotaFiscalNota',
        'Create:ControleNotaFiscalNota',
        'Update:ControleNotaFiscalNota',
    ]);

    $this->actingAs($user);

    ['nota' => $nota] = createControleNotaFiscalComNota($user);

    visit(ImportacaoNotaFiscalResource::getUrl('index'))->assertPathIs('/admin/importacao-notas-fiscais');
    visit(ImportacaoNotaFiscalResource::getUrl('create'))->assertPathIs('/admin/importacao-notas-fiscais/create');
    visit(ImportacaoNotaFiscalResource::getUrl('edit', ['record' => $nota]))->assertPathIs("/admin/importacao-notas-fiscais/{$nota->id}/edit");
});
