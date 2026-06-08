<?php

use App\Enums\AsStatus;
use App\Enums\StatusControleNotaFiscalNota;
use App\Filament\Resources\ControleNotaFiscals\Pages\EditControleNotaFiscal;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

beforeEach(function () {
    setupFilamentResourceCoverageForTests($this);
});

it('destaca badge e saldos negativos no controle de medição', function () {
    $user = createFinanceiroUserWithPermissions([
        'ViewAny:ControleNotaFiscal',
        'View:ControleNotaFiscal',
        'Update:ControleNotaFiscal',
    ], asSuperAdmin: true);

    $this->actingAs($user);

    ['controle' => $controle, 'nota' => $nota] = createControleNotaFiscalComNota($user);
    $controle->itens()->firstOrFail()->autorizacaoServico()->update([
        'status' => AsStatus::ENVIADA,
    ]);

    $nota->update([
        'status' => StatusControleNotaFiscalNota::APROVADO->value,
        'valor_acumulado_medido_nf' => 700,
    ]);

    Livewire::test(EditControleNotaFiscal::class, ['record' => $controle->getRouteKey()])
        ->assertSet('sheetRows.0.notas_mao_obra.0.status', StatusControleNotaFiscalNota::APROVADO->value)
        ->assertSet('sheetRows.0.notas_mao_obra.0.valor_acumulado_medido_nf', '700.00')
        ->assertSee('Saldo negativo')
        ->assertSee('SALDO NEGATIVO')
        ->assertSeeHtml('cmed-inline-badge cmed-inline-badge-danger')
        ->assertSeeHtml('cmed-metric cmed-metric-danger')
        ->assertSeeHtml('cmed-finance-header-summary cmed-finance-header-summary-danger');
});
