<?php

use App\Enums\ModoAncoraCronograma;
use App\Models\Projeto;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('projeto novo nasce com modo_ancora=POSSE', function () {
    $projeto = Projeto::factory()->create();

    expect($projeto->modo_ancora)->toBe(ModoAncoraCronograma::POSSE);
});

it('após aplicar template, modo_ancora vira OBRAS automaticamente', function () {
    $projeto = aplicarSmartFit('2026-08-01');

    expect($projeto->modo_ancora)->toBe(ModoAncoraCronograma::OBRAS);
});

it('em modo OBRAS, alterar data_posse NÃO recalcula o cronograma', function () {
    $projeto = aplicarSmartFit('2026-08-01');
    // aplicarSmartFit já deixou em modo OBRAS
    expect($projeto->modo_ancora)->toBe(ModoAncoraCronograma::OBRAS);

    $executivo = $projeto->cronogramaFases()->where('fase', 'executivo')->first();
    $execInicioAntes = $executivo->data_prevista_inicio?->toDateString();

    // Move a posse +30 dias — em modo OBRAS, executivo NÃO deve mover
    $projeto->update(['data_posse' => '2026-09-01']);
    $executivo->refresh();

    expect($executivo->data_prevista_inicio?->toDateString())->toBe($execInicioAntes);
});

it('em modo POSSE, alterar data_posse para frente recalcula o cronograma', function () {
    $projeto = aplicarSmartFit('2026-08-01');
    $projeto->update(['modo_ancora' => ModoAncoraCronograma::POSSE->value]);
    $projeto->refresh();

    $executivo = $projeto->cronogramaFases()->where('fase', 'executivo')->first();
    $execInicioAntes = $executivo->data_prevista_inicio?->toDateString();

    // Adia posse +60 dias — em modo POSSE, executivo deve mover junto
    $projeto->update(['data_posse' => '2026-10-01']);
    $executivo->refresh();

    expect($executivo->data_prevista_inicio?->toDateString())->not->toBe($execInicioAntes);
});

it('label e cor do enum ModoAncoraCronograma estão consistentes', function () {
    expect(ModoAncoraCronograma::POSSE->label())->toBe('Ancorado em Posse')
        ->and(ModoAncoraCronograma::OBRAS->label())->toBe('Ancorado em Obras');
});
