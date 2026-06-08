<?php

use App\Enums\FaseCronograma;
use App\Enums\StatusCronograma;
use App\Models\CronogramaFase;
use App\Services\CronogramaTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('quando ASSINATURA_CONTRATO vira ASSINADO, fases finais ficam bloqueadas', function () {
    $projeto = aplicarSmartFit('2026-08-01');

    // Sanity check: antes nenhuma fase está bloqueada
    $finais = CronogramaFase::where('projeto_id', $projeto->id)
        ->whereIn('fase', [
            FaseCronograma::OBRAS,
            FaseCronograma::IMPLANTACAO,
            FaseCronograma::INAUGURACAO,
        ])
        ->get();

    foreach ($finais as $f) {
        expect($f->bloqueada_pos_contrato)->toBeFalse();
    }

    // Transita ASSINATURA_CONTRATO → ASSINADO
    $assinatura = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::ASSINATURA_CONTRATO)
        ->first();
    $assinatura->update(['status' => StatusCronograma::ASSINADO]);

    // Refresh e checa cadeado
    foreach ($finais as $f) {
        $f->refresh();
        expect($f->bloqueada_pos_contrato)->toBeTrue();
    }
});

it('faseBloqueada combina status finalizador OU bloqueada_pos_contrato', function () {
    $projeto = aplicarSmartFit('2026-08-01');
    $obras = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::OBRAS)->first();

    expect(CronogramaTemplateService::faseBloqueada($obras))->toBeFalse();

    // Apenas o flag de cadeado
    $obras->update(['bloqueada_pos_contrato' => true]);
    $obras->refresh();
    expect(CronogramaTemplateService::faseBloqueada($obras))->toBeTrue();

    // Apenas o status finalizador
    $obras->update(['bloqueada_pos_contrato' => false, 'status' => StatusCronograma::CONCLUIDO]);
    $obras->refresh();
    expect(CronogramaTemplateService::faseBloqueada($obras))->toBeTrue();
});

it('transição para status NÃO-ASSINADO não trava as fases finais', function () {
    $projeto = aplicarSmartFit('2026-08-01');
    $assinatura = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::ASSINATURA_CONTRATO)
        ->first();

    $assinatura->update(['status' => StatusCronograma::MINUTA]);

    $obras = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::OBRAS)->first();

    expect($obras->bloqueada_pos_contrato)->toBeFalse();
});

it('status do contrato propaga porcentagem proporcional para a fase ASSINATURA_CONTRATO', function () {
    $projeto = aplicarSmartFit('2026-08-01');
    $assinatura = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::ASSINATURA_CONTRATO)
        ->first();

    $assinatura->update(['status' => StatusCronograma::NEGOCIACAO]);
    $assinatura->refresh();
    expect($assinatura->percentual_conclusao)->toBe(0);

    $assinatura->update(['status' => StatusCronograma::MINUTA]);
    $assinatura->refresh();
    expect($assinatura->percentual_conclusao)->toBe(25);

    $assinatura->update(['status' => StatusCronograma::EM_ASSINATURA]);
    $assinatura->refresh();
    expect($assinatura->percentual_conclusao)->toBe(50);

    $assinatura->update(['status' => StatusCronograma::ASSINADO]);
    $assinatura->refresh();
    expect($assinatura->percentual_conclusao)->toBe(100);
});
