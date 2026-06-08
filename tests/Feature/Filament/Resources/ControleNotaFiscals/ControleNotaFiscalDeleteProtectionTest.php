<?php

use App\Filament\Resources\ControleNotaFiscals\Pages\EditControleNotaFiscal;
use App\Filament\Resources\ControleNotaFiscals\Pages\ListControleNotaFiscals;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

beforeEach(function () {
    setupFilamentResourceCoverageForTests($this);
});

it('nao expõe ações de exclusão e bloqueia a habilidade de deletar o controle', function () {
    $user = createFinanceiroUserWithPermissions([
        'ViewAny:ControleNotaFiscal',
        'View:ControleNotaFiscal',
        'Update:ControleNotaFiscal',
        'Delete:ControleNotaFiscal',
        'DeleteAny:ControleNotaFiscal',
        'ForceDelete:ControleNotaFiscal',
        'ForceDeleteAny:ControleNotaFiscal',
    ], asSuperAdmin: true);

    $this->actingAs($user);

    ['controle' => $controle] = createControleNotaFiscalComNota($user);

    expect($user->can('delete', $controle))->toBeFalse()
        ->and($user->can('deleteAny', $controle::class))->toBeFalse();

    Livewire::test(EditControleNotaFiscal::class, ['record' => $controle->getRouteKey()])
        ->assertActionDoesNotExist('deleteControle');

    Livewire::test(ListControleNotaFiscals::class)
        ->assertTableBulkActionDoesNotExist('delete');
});
