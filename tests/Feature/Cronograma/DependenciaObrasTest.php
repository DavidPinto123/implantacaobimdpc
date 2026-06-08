<?php

use App\Enums\FaseCronograma;
use App\Enums\StatusCronograma;
use App\Enums\StatusLiberacaoPosse;
use App\Models\CronogramaFase;
use App\Observers\CronogramaFaseObserver;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lista pendências quando OBRAS não tem pré-requisitos atendidos', function () {
    $projeto = aplicarSmartFit('2026-08-01');
    $obras = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::OBRAS)
        ->firstOrFail();

    $pendencias = (new CronogramaFaseObserver)->checarPreRequisitosObras($obras);

    expect($pendencias)->toContain('Orçamentos não iniciado')
        ->and($pendencias)->toContain('Engenharia (Liberação de Posse) não confirmada')
        ->and($pendencias)->toContain('Legalização (Liberação de Posse) não confirmada');
});

it('bloqueia início de OBRAS quando Orçamentos não foi iniciado', function () {
    $projeto = aplicarSmartFit('2026-08-01');
    $obras = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::OBRAS)
        ->firstOrFail();

    // Atende as outras duas dependências (Engenharia + Legalização)
    $liberacao = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::LIBERACAO_POSSE)
        ->firstOrFail();
    $liberacao->itens()->where('titulo', 'Engenharia')->update(['status_liberacao' => StatusLiberacaoPosse::SIM]);
    $liberacao->itens()->where('titulo', 'Legalização')->update(['status_liberacao' => StatusLiberacaoPosse::SIM]);

    $obras->status = StatusCronograma::EM_ANDAMENTO;
    $result = $obras->save();

    expect($result)->toBeFalse()
        ->and($obras->fresh()->status)->toBe(StatusCronograma::NAO_INICIADO);
});

it('permite início de OBRAS quando todos os pré-requisitos estão atendidos', function () {
    $projeto = aplicarSmartFit('2026-08-01');

    // Conclui orçamentos
    CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::ORCAMENTOS)
        ->update(['data_realizada_inicio' => now()->toDateString()]);

    // Marca Engenharia + Legalização como SIM
    $liberacao = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::LIBERACAO_POSSE)
        ->firstOrFail();
    $liberacao->itens()->where('titulo', 'Engenharia')->update(['status_liberacao' => StatusLiberacaoPosse::SIM]);
    $liberacao->itens()->where('titulo', 'Legalização')->update(['status_liberacao' => StatusLiberacaoPosse::SIM]);

    $obras = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::OBRAS)
        ->firstOrFail();

    $obras->status = StatusCronograma::EM_ANDAMENTO;
    $result = $obras->save();

    expect($result)->toBeTrue()
        ->and($obras->fresh()->status)->toBe(StatusCronograma::EM_ANDAMENTO);
});

it('não bloqueia outras fases que não sejam OBRAS', function () {
    $projeto = aplicarSmartFit('2026-08-01');
    $cad = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::LEVANTAMENTO_CADASTRAL)
        ->firstOrFail();

    $cad->status = StatusCronograma::EM_ANDAMENTO;
    $result = $cad->save();

    expect($result)->toBeTrue();
});
