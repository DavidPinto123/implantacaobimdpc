<?php

use App\Enums\FaseCronograma;
use App\Enums\StatusCronograma;
use App\Models\CronogramaFase;
use App\Services\CronogramaTemplateService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

it('aumentar Prazo Legal via override empurra Obras (recalcularFaseEDependentes)', function () {
    $projeto = aplicarSmartFit('2026-08-01');

    $obrasAntes = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::OBRAS)->first();
    $inicioObrasAntes = $obrasAntes->data_prevista_inicio->toDateString();

    $prazo = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::PRAZO_LEGAL)->first();

    $prazo->update([
        'regra_duracao_dias' => 120,
        'regra_customizada' => true,
    ]);

    // Usuário típico aciona via mudança manual de data → recalcular cascata desde Prazo Legal.
    app(CronogramaTemplateService::class)->recalcularFaseEDependentes($prazo->fresh());

    $obrasAntes->refresh();
    expect($obrasAntes->data_prevista_inicio->toDateString())->not->toBe($inicioObrasAntes);
    expect($obrasAntes->data_prevista_inicio->gt(CarbonImmutable::parse($inicioObrasAntes)))->toBeTrue();
});

it('SUFRAMA aplicado vira fase pai oculta com 3 subitens dependentes', function () {
    $projeto = aplicarSmartFit('2026-08-01');

    $suframa = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::SUFRAMA)->first();

    expect($suframa)->not->toBeNull();
    expect($suframa->visivel)->toBeFalse();
    expect($suframa->itens->count())->toBe(3);

    $pin = $suframa->itens->firstWhere('titulo', 'PIN Suframa');
    expect($pin->dependencias->count())->toBe(1);

    $dep = $pin->dependencias->first();
    expect((int) $dep->gap_dias)->toBe(-45);
    expect($dep->gatilho->value)->toBe('fim_antes_inicio');
});

it('Briefing tem fim no Levantamento Cadastral + 1 (FIM_ANTERIOR)', function () {
    $projeto = aplicarSmartFit('2026-08-01');

    $brief = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::BRIEFING)->first();
    $lev = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::LEVANTAMENTO_CADASTRAL)->first();

    expect($brief->data_prevista_inicio->toDateString())
        ->toBe($lev->data_prevista_fim->copy()->addDay()->toDateString());
});

it('Fase 1 elástica termina 1 dia antes do Briefing', function () {
    $projeto = aplicarSmartFit('2026-08-01');

    $brief = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::BRIEFING)->first();
    $fase1 = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::RECEBIMENTO_PROJETOS_ARQUITETURA)->first();

    expect($fase1->data_prevista_fim->toDateString())
        ->toBe($brief->data_prevista_inicio->copy()->subDay()->toDateString());
});

it('Marketing elástico cobre do Início do Projeto à Inauguração', function () {
    $projeto = aplicarSmartFit('2026-08-01');

    $mkt = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::MKT_ATIVACAO_PRE_VENDAS)->first();
    $inicioProj = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::INICIO_PROJETO)->first();
    $inau = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::INAUGURACAO)->first();

    expect($mkt->data_prevista_inicio->toDateString())
        ->toBe($inicioProj->data_prevista_inicio->toDateString());
    expect($mkt->data_prevista_fim->toDateString())
        ->toBe($inau->data_prevista_fim->toDateString());
});

it('cascata respeita fases concluídas: status final bloqueia recalcular', function () {
    $projeto = aplicarSmartFit('2026-08-01');

    $brief = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::BRIEFING)->first();
    $brief->update(['status' => StatusCronograma::CONCLUIDO]);
    $brief->refresh();
    $dataConcluida = $brief->data_prevista_inicio->toDateString();

    // Tenta puxar via sincronização — fase concluída não deve mover.
    $projeto->update(['data_posse' => '2026-09-30']);
    app(CronogramaTemplateService::class)->sincronizarDatasComProjeto($projeto);

    $brief->refresh();
    expect($brief->data_prevista_inicio->toDateString())->toBe($dataConcluida);
});

it('recalcularFaseEDependentes propaga forward sem tocar fases backward', function () {
    $projeto = aplicarSmartFit('2026-08-01');

    $assinatura = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::ASSINATURA_CONTRATO)->first();
    $dataAntes = $assinatura->data_prevista_inicio->toDateString();

    $obras = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::OBRAS)->first();

    // Aumenta Obras manualmente
    $obras->update([
        'regra_duracao_dias' => 120,
        'regra_customizada' => true,
    ]);

    app(CronogramaTemplateService::class)->recalcularFaseEDependentes($obras->fresh());

    // Implantação (descendente) atualiza
    $impl = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::IMPLANTACAO)->first();
    expect($impl->data_prevista_inicio->gt($obras->data_prevista_fim))->toBeTrue();

    // Assinatura (backward) NÃO muda
    $assinatura->refresh();
    expect($assinatura->data_prevista_inicio->toDateString())->toBe($dataAntes);
});

it('mudar data_posse desloca todas as fases não-concluídas', function () {
    $projeto = aplicarSmartFit('2026-08-01');

    $obrasAntes = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::OBRAS)->first();
    $inicioObrasAntes = $obrasAntes->data_prevista_inicio->copy();

    $projeto->update(['data_posse' => '2026-09-15']);
    app(CronogramaTemplateService::class)->sincronizarDatasComProjeto($projeto);

    $obrasAntes->refresh();
    $delta = (int) $inicioObrasAntes->diffInDays($obrasAntes->data_prevista_inicio, absolute: true);

    expect($delta)->toBe(45); // 30/08 → 15/09 = 45 dias
});

it('Inauguração coincide com fim de Implantação (FIM_ANTERIOR_MESMO_DIA)', function () {
    $projeto = aplicarSmartFit('2026-08-01');

    $impl = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::IMPLANTACAO)->first();
    $inau = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::INAUGURACAO)->first();

    expect($inau->data_prevista_inicio->toDateString())
        ->toBe($impl->data_prevista_fim->toDateString());
    expect($inau->data_prevista_fim->toDateString())
        ->toBe($impl->data_prevista_fim->toDateString());
});
