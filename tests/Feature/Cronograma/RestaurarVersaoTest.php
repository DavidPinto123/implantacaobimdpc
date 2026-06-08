<?php

use App\Enums\FaseCronograma;
use App\Filament\Pages\Cronograma;
use App\Models\CronogramaFase;
use App\Models\CronogramaFaseHistorico;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('restaurarVersao reverte data prevista da fase para o estado salvo no timestamp', function () {
    $projeto = aplicarSmartFit('2026-08-01');
    $fase = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::VISITA_TECNICA)->first();

    $dataOriginal = $fase->data_prevista_inicio->toDateString();

    // Marca um timestamp ANTES da alteração
    sleep(1);
    $timestamp = now()->subSecond()->format('Y-m-d H:i:s');
    sleep(1);

    // Faz alteração + grava histórico
    $novaData = $fase->data_prevista_inicio->copy()->addDays(7);
    CronogramaFaseHistorico::create([
        'projeto_id' => $projeto->id,
        'cronograma_fase_id' => $fase->id,
        'campo_alterado' => 'data_prevista_inicio',
        'valor_anterior' => $dataOriginal,
        'valor_novo' => $novaData->toDateString(),
        'motivo' => 'Test',
        'usuario_id' => 1,
        'automatico' => false,
    ]);
    $fase->update(['data_prevista_inicio' => $novaData]);
    $fase->refresh();
    expect($fase->data_prevista_inicio->toDateString())->toBe($novaData->toDateString());

    // Restaura
    $page = new Cronograma;
    $page->projetoSelecionado = $projeto->id;
    $page->restaurarVersao($timestamp);

    $fase->refresh();
    expect($fase->data_prevista_inicio->toDateString())->toBe($dataOriginal);
});

it('restaurarVersao grava nova entry no histórico com motivo "Restauração de versão"', function () {
    $projeto = aplicarSmartFit('2026-08-01');
    $fase = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::VISITA_TECNICA)->first();

    sleep(1);
    $timestamp = now()->subSecond()->format('Y-m-d H:i:s');
    sleep(1);

    $novaData = $fase->data_prevista_inicio->copy()->addDays(5);
    CronogramaFaseHistorico::create([
        'projeto_id' => $projeto->id,
        'cronograma_fase_id' => $fase->id,
        'campo_alterado' => 'data_prevista_inicio',
        'valor_anterior' => $fase->data_prevista_inicio->toDateString(),
        'valor_novo' => $novaData->toDateString(),
        'motivo' => 'Test',
        'usuario_id' => 1,
        'automatico' => false,
    ]);
    $fase->update(['data_prevista_inicio' => $novaData]);

    $page = new Cronograma;
    $page->projetoSelecionado = $projeto->id;
    $page->restaurarVersao($timestamp);

    $historicoRestauracao = CronogramaFaseHistorico::where('projeto_id', $projeto->id)
        ->where('motivo', 'like', 'Restauração de versão%')
        ->first();

    expect($historicoRestauracao)->not->toBeNull()
        ->and($historicoRestauracao->motivo)->toContain('Restauração de versão');
});
