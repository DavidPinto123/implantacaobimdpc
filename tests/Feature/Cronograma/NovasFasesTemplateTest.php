<?php

use App\Enums\FaseCronograma;
use App\Models\CronogramaFase;
use App\Models\CronogramaFaseItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('template Smart Fit cria as 3 fases novas (CNPJ Legalizacao, Liberacao Posse, Entregas PP)', function () {
    $projeto = aplicarSmartFit('2026-08-01');

    $fases = CronogramaFase::where('projeto_id', $projeto->id)
        ->whereIn('fase', [
            FaseCronograma::CNPJ_LEGALIZACAO,
            FaseCronograma::LIBERACAO_POSSE,
            FaseCronograma::ENTREGAS_PROPRIETARIO,
        ])
        ->pluck('fase')
        ->map(fn ($f) => $f instanceof FaseCronograma ? $f->value : $f)
        ->toArray();

    expect($fases)->toContain('cnpj_legalizacao')
        ->and($fases)->toContain('liberacao_posse')
        ->and($fases)->toContain('entregas_proprietario');
});

it('CNPJ Legalizacao começa 1 dia após assinatura e dura 60 dias', function () {
    $projeto = aplicarSmartFit('2026-08-01');

    $cnpj = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::CNPJ_LEGALIZACAO)->first();
    $assinatura = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::ASSINATURA_CONTRATO)->first();

    // FIM_ANTERIOR gap=0 → começa 1 dia após o fim da assinatura
    expect($cnpj->data_prevista_inicio?->toDateString())
        ->toBe($assinatura->data_prevista_fim?->copy()->addDay()->toDateString());

    // Duração 60 dias inclusivo → fim = inicio + 59 dias
    expect($cnpj->data_prevista_inicio->diffInDays($cnpj->data_prevista_fim))->toBe(59);
});

it('Entregas do Proprietário tem 4 subitens datados', function () {
    $projeto = aplicarSmartFit('2026-08-01');

    $entregas = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::ENTREGAS_PROPRIETARIO)
        ->with('itens')
        ->first();

    expect($entregas->itens)->toHaveCount(4);

    $titulos = $entregas->itens->pluck('titulo')->toArray();
    expect($titulos)->toContain('Entrega de projeto contratual (PP → SF)')
        ->and($titulos)->toContain('Retorno SF: Layout')
        ->and($titulos)->toContain('Retorno SF: Planta técnica')
        ->and($titulos)->toContain('Prazo entrega Shell');
});

it('OBRAS tem Energia Smart Fit e Energia Proprietário como subitens (reunião 11/05)', function () {
    $projeto = aplicarSmartFit('2026-08-01');

    $obras = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::OBRAS)
        ->with('itens')
        ->first();

    $titulos = $obras->itens->pluck('titulo')->toArray();

    expect($titulos)->toContain('Energia Smart Fit')
        ->and($titulos)->toContain('Energia Proprietário');
});

it('OBRAS depende de LIBERACAO_POSSE (dep tripla com orcamentos e posse)', function () {
    $projeto = aplicarSmartFit('2026-08-01');

    $obras = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::OBRAS)
        ->with('templateFase.dependencias')
        ->first();

    $depFases = $obras->templateFase->dependencias
        ->pluck('depende_de_fase')
        ->map(fn ($f) => $f instanceof FaseCronograma ? $f->value : $f)
        ->toArray();

    expect($depFases)->toContain('liberacao_posse');
});
