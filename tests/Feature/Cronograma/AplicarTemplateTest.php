<?php

use App\Enums\FaseCronograma;
use App\Enums\StatusCronograma;
use App\Enums\TipoObraCronograma;
use App\Models\CronogramaFase;
use App\Models\CronogramaTemplate;
use App\Models\CronogramaTemplateFase;
use App\Models\Projeto;
use App\Services\CronogramaTemplateService;
use Carbon\CarbonImmutable;
use Database\Factories\ProjetoFactory;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

it('aplica template Smart Fit em projeto novo: persiste 22 fases', function () {
    $projeto = aplicarSmartFit('2026-08-01');

    $fases = CronogramaFase::where('projeto_id', $projeto->id)->get();
    expect($fases)->toHaveCount(22);

    // POSSE em data correta.
    $posse = $fases->firstWhere('fase', FaseCronograma::POSSE);
    expect($posse->data_prevista_inicio?->toDateString())->toBe('2026-08-01');
});

it('SUFRAMA persiste como fase pai oculta com 3 subitens (CNPJ, PIN, Compras)', function () {
    $projeto = aplicarSmartFit('2026-08-01');

    $suframa = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::SUFRAMA)
        ->first();

    expect($suframa)->not->toBeNull();
    expect($suframa->isVisivel())->toBeFalse();

    $titulos = $suframa->itens->pluck('titulo')->all();
    expect($titulos)
        ->toContain('CNPJ Suframa')
        ->toContain('PIN Suframa')
        ->toContain('Compras Suframa');
});

it('aplicar duas vezes não duplica fases', function () {
    $template = seedTemplateSmartFit();
    $projeto = ProjetoFactory::new()->create(['data_posse' => '2026-08-01']);
    $service = app(CronogramaTemplateService::class);

    $service->aplicar($template, $projeto);
    expect(CronogramaFase::where('projeto_id', $projeto->id)->count())->toBe(22);

    $service->aplicar($template, $projeto);
    expect(CronogramaFase::where('projeto_id', $projeto->id)->count())->toBe(22);
});

it('re-aplicar reseta overrides locais (comportamento atual)', function () {
    // Comportamento atual: aplicar() limpa regra_duracao_dias/regra_customizada/regra_elastica.
    // Trava semântica para que mudanças futuras sejam intencionais.
    $projeto = aplicarSmartFit('2026-08-01');

    $exec = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::EXECUTIVO)
        ->first();

    $exec->update([
        'regra_duracao_dias' => 45,
        'regra_customizada' => true,
    ]);

    $template = seedTemplateSmartFit();
    app(CronogramaTemplateService::class)->aplicar($template, $projeto);

    $exec->refresh();
    expect($exec->regra_duracao_dias)->toBeNull();
    expect($exec->regra_customizada)->toBeFalse();
});

it('re-aplicar recalcula datas das fases sem override', function () {
    $projeto = aplicarSmartFit('2026-08-01');

    $kickoff = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::KICKOFF)
        ->first();

    $dataOriginal = $kickoff->data_prevista_inicio;

    // Mudar data_posse + reaplicar
    $projeto->update(['data_posse' => '2026-09-01']);
    $template = seedTemplateSmartFit();
    app(CronogramaTemplateService::class)->aplicar($template, $projeto);

    $kickoff->refresh();
    expect($kickoff->data_prevista_inicio->ne($dataOriginal))->toBeTrue();
});

it('sincronizarDatasComProjeto: ao mudar data_posse, todas as fases recalculam', function () {
    $projeto = aplicarSmartFit('2026-08-01');

    $obras = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::OBRAS)
        ->first();
    $dataOriginalInicio = $obras->data_prevista_inicio;

    $projeto->update(['data_posse' => '2026-09-30']);
    app(CronogramaTemplateService::class)->sincronizarDatasComProjeto($projeto);

    $obras->refresh();
    expect($obras->data_prevista_inicio->gt($dataOriginalInicio))->toBeTrue();
});

it('aplicar template sem fase âncora lança RuntimeException', function () {
    $template = CronogramaTemplate::create([
        'nome' => 'Sem âncora',
        'tipo_obra' => TipoObraCronograma::EXPANSAO->value,
        'ancora_campo' => 'projeto.data_posse',
        'ativo' => true,
    ]);

    CronogramaTemplateFase::create([
        'cronograma_template_id' => $template->id,
        'fase' => FaseCronograma::POSSE,
        'ordem' => FaseCronograma::POSSE->ordem(),
        'duracao_dias' => 0,
        'tipo_dias' => 'corridos',
        'visivel' => true,
        'is_ancora' => false, // sem âncora
    ]);

    $projeto = ProjetoFactory::new()->create(['data_posse' => '2026-08-01']);

    expect(fn () => app(CronogramaTemplateService::class)->aplicar($template, $projeto))
        ->toThrow(RuntimeException::class);
});

it('aplicar template em projeto sem campo âncora preenchido lança RuntimeException', function () {
    $template = seedTemplateSmartFit();
    $projeto = ProjetoFactory::new()->create(['data_posse' => null]);

    expect(fn () => app(CronogramaTemplateService::class)->aplicar($template, $projeto))
        ->toThrow(RuntimeException::class);
});

it('aplicar template registra cronograma_template_id em cada fase', function () {
    $template = seedTemplateSmartFit();
    $projeto = ProjetoFactory::new()->create(['data_posse' => '2026-08-01']);
    app(CronogramaTemplateService::class)->aplicar($template, $projeto);

    $fases = CronogramaFase::where('projeto_id', $projeto->id)
        ->whereNotNull('cronograma_template_fase_id')
        ->get();

    // Todas as fases criadas pelo aplicar() devem ter ambas as referências.
    expect($fases->count())->toBe(22);
    foreach ($fases as $fase) {
        expect($fase->cronograma_template_id)->toBe($template->id);
    }
});
