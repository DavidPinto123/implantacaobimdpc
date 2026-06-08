<?php

use App\Enums\FaseCronograma;
use App\Models\CronogramaFase;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('aplicar template cria fase LIBERACAO_POSSE entre PRAZO_LEGAL e POSSE', function () {
    $projeto = aplicarSmartFit('2026-08-01');

    $fase = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::LIBERACAO_POSSE)
        ->first();

    expect($fase)->not->toBeNull();
});

it('LIBERACAO_POSSE termina 1 dia antes da Posse', function () {
    $projeto = aplicarSmartFit('2026-08-01');

    $posse = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::POSSE)->first();
    $liberacao = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::LIBERACAO_POSSE)->first();

    expect($liberacao->data_prevista_fim?->toDateString())
        ->toBe($posse->data_prevista_inicio?->copy()->subDay()->toDateString());
});

it('LIBERACAO_POSSE começa na data da Assinatura do Contrato (elástica)', function () {
    $projeto = aplicarSmartFit('2026-08-01');

    $assinatura = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::ASSINATURA_CONTRATO)->first();
    $liberacao = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::LIBERACAO_POSSE)->first();

    expect($liberacao->data_prevista_inicio?->toDateString())
        ->toBe($assinatura->data_prevista_inicio?->toDateString());
});

it('LIBERACAO_POSSE tem 2 subitens padrão (engenharia + legalização)', function () {
    $projeto = aplicarSmartFit('2026-08-01');

    $liberacao = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::LIBERACAO_POSSE)
        ->with('itens')
        ->first();

    expect($liberacao->itens)->toHaveCount(2);

    $titulos = $liberacao->itens->pluck('titulo')->toArray();
    expect($titulos)->toContain('Liberação Engenharia')
        ->and($titulos)->toContain('Liberação Legalização');
});

it('fase LIBERACAO_POSSE é marcada como elástica no template', function () {
    $projeto = aplicarSmartFit('2026-08-01');

    $liberacao = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::LIBERACAO_POSSE)
        ->with('templateFase')
        ->first();

    expect($liberacao->templateFase?->regra_elastica)->toBeTrue();
});
