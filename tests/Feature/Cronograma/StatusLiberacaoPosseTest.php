<?php

use App\Enums\FaseCronograma;
use App\Enums\StatusLiberacaoPosse;
use App\Models\CronogramaFase;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('subitens de Liberação de Posse não usam mais o prefixo "Liberação"', function () {
    $projeto = aplicarSmartFit('2026-08-01');
    $fase = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::LIBERACAO_POSSE)
        ->firstOrFail();

    $titulos = $fase->itens->pluck('titulo')->all();

    expect($titulos)->toContain('Engenharia')
        ->and($titulos)->toContain('Legalização')
        ->and($titulos)->not->toContain('Liberação Engenharia')
        ->and($titulos)->not->toContain('Liberação Legalização');
});

it('status_liberacao SIM sincroniza recebido=true automaticamente', function () {
    $projeto = aplicarSmartFit('2026-08-01');
    $item = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::LIBERACAO_POSSE)
        ->firstOrFail()
        ->itens()
        ->where('titulo', 'Engenharia')
        ->firstOrFail();

    $item->status_liberacao = StatusLiberacaoPosse::SIM;
    $item->save();
    $item->refresh();

    expect($item->status_liberacao)->toBe(StatusLiberacaoPosse::SIM)
        ->and($item->recebido)->toBeTrue();
});

it('status_liberacao RISCO sincroniza recebido=false', function () {
    $projeto = aplicarSmartFit('2026-08-01');
    $item = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::LIBERACAO_POSSE)
        ->firstOrFail()
        ->itens()
        ->where('titulo', 'Legalização')
        ->firstOrFail();

    $item->status_liberacao = StatusLiberacaoPosse::RISCO;
    $item->save();
    $item->refresh();

    expect($item->status_liberacao)->toBe(StatusLiberacaoPosse::RISCO)
        ->and($item->recebido)->toBeFalse();
});

it('status_liberacao NAO sincroniza recebido=false', function () {
    $projeto = aplicarSmartFit('2026-08-01');
    $item = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::LIBERACAO_POSSE)
        ->firstOrFail()
        ->itens()
        ->first();

    $item->status_liberacao = StatusLiberacaoPosse::NAO;
    $item->save();
    $item->refresh();

    expect($item->recebido)->toBeFalse();
});

it('percentual_conclusao da fase Liberação de Posse considera SIM como concluído', function () {
    $projeto = aplicarSmartFit('2026-08-01');
    $fase = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::LIBERACAO_POSSE)
        ->firstOrFail();

    $eng = $fase->itens->firstWhere('titulo', 'Engenharia');
    $leg = $fase->itens->firstWhere('titulo', 'Legalização');

    $eng->update(['status_liberacao' => StatusLiberacaoPosse::SIM]);
    $leg->update(['status_liberacao' => StatusLiberacaoPosse::RISCO]);

    $fase->refresh();
    expect($fase->percentual_conclusao)->toBe(50);

    $leg->update(['status_liberacao' => StatusLiberacaoPosse::SIM]);
    $fase->refresh();
    expect($fase->percentual_conclusao)->toBe(100);
});
