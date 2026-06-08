<?php

use App\Filament\Resources\ControleNotaFiscals\ControleNotaFiscalResource;
use App\Filament\Resources\ControleNotaFiscals\Pages\ListControleNotaFiscals;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

beforeEach(function () {
    setupFilamentResourceCoverageForTests($this);
});

it('nao registra formulario de criacao para controle de medicao', function () {
    $user = createFinanceiroUserWithPermissions([
        'ViewAny:ControleNotaFiscal',
        'View:ControleNotaFiscal',
        'Create:ControleNotaFiscal',
    ], asSuperAdmin: true);

    $this->actingAs($user);

    expect(ControleNotaFiscalResource::getPages())
        ->not->toHaveKey('create');

    Livewire::test(ListControleNotaFiscals::class)
        ->assertActionDoesNotExist('create');

    $this->get('/admin/controle-notas-fiscais/create')
        ->assertNotFound();
});
