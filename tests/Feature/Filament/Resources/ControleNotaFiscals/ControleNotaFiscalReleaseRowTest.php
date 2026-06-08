<?php

use App\Filament\Resources\ControleNotaFiscals\Pages\EditControleNotaFiscal;
use App\Models\AsEscopo;
use App\Models\AutorizacaoServico;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

beforeEach(function () {
    setupFilamentResourceCoverageForTests($this);
});

it('nao cria AS nem libera fornecedor pelo controle de medicao', function () {
    $user = createFinanceiroUserWithPermissions([
        'ViewAny:ControleNotaFiscal',
        'View:ControleNotaFiscal',
        'Update:ControleNotaFiscal',
    ], asSuperAdmin: true);

    $this->actingAs($user);

    ['controle' => $controle] = createControleNotaFiscalComNota($user);

    $escopo = AsEscopo::create([
        'grupo' => 'Ar Condicionado',
        'numero_as' => '03.1',
        'escopo' => 'AR COND. INSTALAÇÃO',
        'is_active' => true,
    ]);

    Livewire::test(EditControleNotaFiscal::class, ['record' => $controle->getRouteKey()])
        ->assertSee('Controle de Medição')
        ->assertDontSee('Liberar para fornecedor')
        ->assertDontSee('Adicionar escopo');

    expect(method_exists(EditControleNotaFiscal::class, 'attachEscoposSelecionados'))->toBeFalse()
        ->and(method_exists(EditControleNotaFiscal::class, 'releaseRow'))->toBeFalse()
        ->and(AutorizacaoServico::query()
            ->where('obra_id', $controle->obra_id)
            ->where('as_escopo_id', $escopo->id)
            ->exists())->toBeFalse();
});
