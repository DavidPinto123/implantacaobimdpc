<?php

use App\Enums\StatusControleNotaFiscalNota;
use App\Filament\Pages\AprovacaoNotasFiscaisPage;
use App\Models\ControleNotaFiscal;
use App\Models\ControleNotaFiscalNota;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    if (! Route::has('filament.admin.resources.controle-notas-fiscais.view')) {
        Route::get('/_test/controle-notas-fiscais/{record}', fn () => 'ok')
            ->name('filament.admin.resources.controle-notas-fiscais.view');
    }
});

function skipWhenDecisionColumnsAreMissing(): void
{
    if (! Schema::hasColumns('controle_nota_fiscal_notas', ['decidido_por_id', 'decidido_em'])) {
        test()->markTestSkipped('Colunas de decisão (decidido_por_id/decidido_em) ausentes na tabela controle_nota_fiscal_notas.');
    }
}

it('aprova nota apenas quando está em análise e usuário tem permissão', function () {
    skipWhenDecisionColumnsAreMissing();

    $user = createFinanceiroUserWithPermissions([
        'View:AprovacaoNotasFiscaisPage',
        'Update:ControleNotaFiscalNota',
    ]);

    $this->actingAs($user);
    ['item' => $item, 'nota' => $nota] = createControleNotaFiscalComNota($user);

    Livewire::test(AprovacaoNotasFiscaisPage::class)
        ->call('marcarNotaComoVisualizada', $nota->id)
        ->call('aprovar', $nota->id);

    $nota->refresh();

    expect($nota->status)->toBe(StatusControleNotaFiscalNota::APROVADO->value)
        ->and($nota->decidido_por_id)->toBe($user->id)
        ->and($nota->decidido_em)->not->toBeNull()
        ->and((float) $item->refresh()->valor_acumulado_medido)->toBe(150.0)
        ->and((float) $item->saldo)->toBe(850.0);
});

it('não aprova nota já processada mesmo com permissão', function () {
    skipWhenDecisionColumnsAreMissing();

    $user = createFinanceiroUserWithPermissions([
        'View:AprovacaoNotasFiscaisPage',
        'Update:ControleNotaFiscalNota',
    ]);

    $this->actingAs($user);
    ['nota' => $nota] = createControleNotaFiscalComNota($user);

    $nota->update([
        'status' => StatusControleNotaFiscalNota::APROVADO->value,
        'decidido_por_id' => $user->id,
    ]);

    $decididoEmAntes = $nota->decidido_em;

    Livewire::test(AprovacaoNotasFiscaisPage::class)
        ->call('aprovar', $nota->id);

    $nota->refresh();

    expect($nota->status)->toBe(StatusControleNotaFiscalNota::APROVADO->value)
        ->and($nota->decidido_por_id)->toBe($user->id)
        ->and($nota->decidido_em?->toDateTimeString())->toBe($decididoEmAntes?->toDateTimeString());
});

it('não processa aprovação sem permissão de update', function () {
    skipWhenDecisionColumnsAreMissing();

    $user = createFinanceiroUserWithPermissions([
        'View:AprovacaoNotasFiscaisPage',
    ]);

    $this->actingAs($user);
    ['nota' => $nota] = createControleNotaFiscalComNota($user);

    Livewire::test(AprovacaoNotasFiscaisPage::class)
        ->call('aprovar', $nota->id);

    $nota->refresh();

    expect($nota->status)->toBe(StatusControleNotaFiscalNota::EM_ANALISE->value)
        ->and($nota->decidido_por_id)->toBeNull()
        ->and($nota->decidido_em)->toBeNull();
});

it('não aprova nota quando o controle de nota fiscal está encerrado', function () {
    skipWhenDecisionColumnsAreMissing();

    $user = createFinanceiroUserWithPermissions([
        'View:AprovacaoNotasFiscaisPage',
        'Update:ControleNotaFiscalNota',
    ]);

    $this->actingAs($user);
    ['controle' => $controle, 'nota' => $nota] = createControleNotaFiscalComNota($user);

    $controle->update([
        'status' => ControleNotaFiscal::STATUS_ENCERRADO,
    ]);

    Livewire::test(AprovacaoNotasFiscaisPage::class)
        ->call('marcarNotaComoVisualizada', $nota->id)
        ->call('aprovar', $nota->id);

    $nota->refresh();

    expect($nota->status)->toBe(StatusControleNotaFiscalNota::EM_ANALISE->value)
        ->and($nota->decidido_por_id)->toBeNull()
        ->and($nota->decidido_em)->toBeNull();
});

it('reprova nota em análise com permissão', function () {
    skipWhenDecisionColumnsAreMissing();

    $user = createFinanceiroUserWithPermissions([
        'View:AprovacaoNotasFiscaisPage',
        'Update:ControleNotaFiscalNota',
    ]);

    $this->actingAs($user);
    ['item' => $item, 'nota' => $nota] = createControleNotaFiscalComNota($user);

    Livewire::test(AprovacaoNotasFiscaisPage::class)
        ->call('reprovar', $nota->id);

    $nota->refresh();

    expect($nota->status)->toBe(StatusControleNotaFiscalNota::REPROVADO->value)
        ->and($nota->decidido_por_id)->toBe($user->id)
        ->and($nota->decidido_em)->not->toBeNull()
        ->and((float) $item->refresh()->valor_acumulado_medido)->toBe(0.0)
        ->and((float) $item->saldo)->toBe(1000.0);
});

it('calcula saldo da linha considerando notas aprovadas e pendentes, sem reprovadas', function () {
    $page = app(AprovacaoNotasFiscaisPage::class);

    $metrics = $page->calculatePendingRowMetrics([
        'valor_global_a' => 1000,
        'percentual_faturamento_mao_obra' => 60,
        'percentual_faturamento_material' => 40,
        'notas_contexto' => collect([
            (object) [
                'tipo_medicao' => ControleNotaFiscalNota::TIPO_MEDICAO_MAO_OBRA,
                'status' => StatusControleNotaFiscalNota::APROVADO->value,
                'valor_acumulado_medido_nf' => 100,
            ],
            (object) [
                'tipo_medicao' => ControleNotaFiscalNota::TIPO_MEDICAO_MAO_OBRA,
                'status' => StatusControleNotaFiscalNota::EM_ANALISE->value,
                'valor_acumulado_medido_nf' => 50,
            ],
            (object) [
                'tipo_medicao' => ControleNotaFiscalNota::TIPO_MEDICAO_MAO_OBRA,
                'status' => StatusControleNotaFiscalNota::REPROVADO->value,
                'valor_acumulado_medido_nf' => 900,
            ],
            (object) [
                'tipo_medicao' => ControleNotaFiscalNota::TIPO_MEDICAO_MATERIAL,
                'status' => StatusControleNotaFiscalNota::APROVADO->value,
                'valor_acumulado_medido_nf' => 200,
            ],
            (object) [
                'tipo_medicao' => ControleNotaFiscalNota::TIPO_MEDICAO_TRANSPORTE,
                'status' => StatusControleNotaFiscalNota::EM_ANALISE->value,
                'valor_acumulado_medido_nf' => 25,
            ],
            (object) [
                'tipo_medicao' => ControleNotaFiscalNota::TIPO_MEDICAO_MATERIAL,
                'status' => StatusControleNotaFiscalNota::PENDENTE->value,
                'valor_acumulado_medido_nf' => 10,
            ],
            (object) [
                'tipo_medicao' => ControleNotaFiscalNota::TIPO_MEDICAO_MATERIAL,
                'status' => StatusControleNotaFiscalNota::REPROVADO->value,
                'valor_acumulado_medido_nf' => 300,
            ],
        ]),
    ]);

    expect($metrics)
        ->and($metrics['total_medicao_a_menos_b'])->toBe(385.0)
        ->and($metrics['saldo_geral'])->toBe(615.0)
        ->and($metrics['saldo_direto'])->toBe(450.0)
        ->and($metrics['saldo_indireto'])->toBe(165.0);
});
