<?php

use App\Enums\FaseCronograma;
use App\Enums\GatilhoTemplateFase;
use App\Enums\TipoDiasTemplate;
use App\Enums\TipoObraCronograma;
use App\Models\CronogramaFase;
use App\Models\CronogramaTemplate;
use App\Models\CronogramaTemplateFase;
use App\Models\CronogramaTemplateFaseDependencia;
use App\Services\CronogramaTemplateService;
use Carbon\CarbonImmutable;
use Database\Factories\ProjetoFactory;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

it('detecta ciclo simples A→B→A em deps e lança exceção', function () {
    $service = app(CronogramaTemplateService::class);

    $deps = [
        'a' => [
            (object) ['de' => 'b', 'gatilho' => GatilhoTemplateFase::FIM_ANTERIOR, 'gap' => 0],
        ],
        'b' => [
            (object) ['de' => 'a', 'gatilho' => GatilhoTemplateFase::FIM_ANTERIOR, 'gap' => 0],
        ],
    ];

    expect(fn () => $service->calcularDatasFromMaps(
        'a',
        CarbonImmutable::parse('2026-08-01'),
        ['a' => 1, 'b' => 1],
        ['a' => TipoDiasTemplate::CORRIDOS, 'b' => TipoDiasTemplate::CORRIDOS],
        $deps,
    ))->toThrow(InvalidArgumentException::class);
});

it('detecta ciclo transitivo A→B→C→A', function () {
    $service = app(CronogramaTemplateService::class);

    $deps = [
        'a' => [(object) ['de' => 'c', 'gatilho' => GatilhoTemplateFase::FIM_ANTERIOR, 'gap' => 0]],
        'b' => [(object) ['de' => 'a', 'gatilho' => GatilhoTemplateFase::FIM_ANTERIOR, 'gap' => 0]],
        'c' => [(object) ['de' => 'b', 'gatilho' => GatilhoTemplateFase::FIM_ANTERIOR, 'gap' => 0]],
    ];

    expect(fn () => $service->calcularDatasFromMaps(
        'a',
        CarbonImmutable::parse('2026-08-01'),
        ['a' => 1, 'b' => 1, 'c' => 1],
        ['a' => TipoDiasTemplate::CORRIDOS, 'b' => TipoDiasTemplate::CORRIDOS, 'c' => TipoDiasTemplate::CORRIDOS],
        $deps,
    ))->toThrow(InvalidArgumentException::class);
});

it('fase elástica sem dep de início OU sem dep de fim retorna [null, null]', function () {
    $service = app(CronogramaTemplateService::class);

    // Reflexão pra acessar método privado
    $method = new ReflectionMethod($service, 'calcularFaseForward');
    $method->setAccessible(true);

    // Elástica sem dep alguma
    $resultado = $method->invoke(
        $service,
        'fase_x',
        [],
        0,
        TipoDiasTemplate::CORRIDOS,
        ['outra' => ['inicio' => CarbonImmutable::parse('2026-08-01'), 'fim' => CarbonImmutable::parse('2026-08-01')]],
        true, // elástica
    );

    expect($resultado)->toBe([null, null]);
});

it('aplicar template em projeto cujo campo âncora é vazio lança erro', function () {
    $template = seedTemplateSmartFit();
    $projeto = ProjetoFactory::new()->create(['data_posse' => null]);

    expect(fn () => app(CronogramaTemplateService::class)->aplicar($template, $projeto))
        ->toThrow(RuntimeException::class);
});

it('template sem fase ancora=true lança erro ao aplicar', function () {
    $template = CronogramaTemplate::create([
        'nome' => 'No anchor',
        'tipo_obra' => TipoObraCronograma::EXPANSAO->value,
        'ancora_campo' => 'projeto.data_posse',
        'ativo' => true,
    ]);

    CronogramaTemplateFase::create([
        'cronograma_template_id' => $template->id,
        'fase' => FaseCronograma::OBRAS,
        'ordem' => FaseCronograma::OBRAS->ordem(),
        'duracao_dias' => 30,
        'tipo_dias' => 'corridos',
        'visivel' => true,
        'is_ancora' => false,
    ]);

    $projeto = ProjetoFactory::new()->create(['data_posse' => '2026-08-01']);

    expect(fn () => app(CronogramaTemplateService::class)->aplicar($template, $projeto))
        ->toThrow(RuntimeException::class);
});

it('cálculo em dias úteis pula sábado/domingo', function () {
    $service = app(CronogramaTemplateService::class);

    // Quinta 2026-04-30 + 3 dias úteis = quarta 2026-05-06 (pula sáb/dom).
    $deps = [
        'b' => [(object) ['de' => 'a', 'gatilho' => GatilhoTemplateFase::FIM_ANTERIOR, 'gap' => 0]],
    ];

    $resultado = $service->calcularDatasFromMaps(
        'a',
        CarbonImmutable::parse('2026-04-30'), // quinta-feira
        ['a' => 0, 'b' => 3],
        ['a' => TipoDiasTemplate::UTEIS, 'b' => TipoDiasTemplate::UTEIS],
        $deps,
    );

    // a (0d) começa quinta 30/04, fim 30/04
    // b começa sexta 01/05 (próximo dia útil após fim de a). Mas 01/05 (Dia do Trabalhador no Brasil) é feriado?
    // O cálculo padrão considera só sábado/domingo, não feriados.
    // 01/05 = sexta-feira → dia útil. b dura 3d úteis: 01, 04, 05 (pula sáb/dom).
    expect($resultado['b']['inicio']->toDateString())->toBe('2026-05-01');
    expect($resultado['b']['fim']->toDateString())->toBe('2026-05-05');
});

it('PRAZO_LEGAL com nao_se_aplica=true não atrasa OBRAS', function () {
    $projeto = aplicarSmartFit('2026-08-01');

    $prazo = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::PRAZO_LEGAL)->first();

    // Aumenta MUITO Prazo Legal mas marca como não visível (simula "não se aplica").
    $prazo->update([
        'regra_duracao_dias' => 365,
        'regra_customizada' => true,
        'visivel' => false,
    ]);

    // sincroniza com nova âncora
    $projeto->update(['data_posse' => '2026-09-15']);
    app(CronogramaTemplateService::class)->sincronizarDatasComProjeto($projeto);

    $obras = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::OBRAS)->first();
    $posse = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::POSSE)->first();

    // Quando prazo legal não é visível, extrairRegrasEfetivas filtra.
    // Obras passa a depender só de Posse (+61 dias = 1 natural + 60 de gordura).
    expect($obras->data_prevista_inicio->toDateString())
        ->toBe($posse->data_prevista_inicio->copy()->addDays(61)->toDateString());
});

it('regra_elastica em fase sem deps adequadas: cálculo retorna sem persistir', function () {
    $template = CronogramaTemplate::create([
        'nome' => 'Elastic broken',
        'tipo_obra' => TipoObraCronograma::EXPANSAO->value,
        'ancora_campo' => 'projeto.data_posse',
        'ativo' => true,
    ]);

    $posse = CronogramaTemplateFase::create([
        'cronograma_template_id' => $template->id,
        'fase' => FaseCronograma::POSSE,
        'ordem' => FaseCronograma::POSSE->ordem(),
        'duracao_dias' => 0,
        'tipo_dias' => 'corridos',
        'visivel' => true,
        'is_ancora' => true,
    ]);

    $mkt = CronogramaTemplateFase::create([
        'cronograma_template_id' => $template->id,
        'fase' => FaseCronograma::MKT_ATIVACAO_PRE_VENDAS,
        'ordem' => FaseCronograma::MKT_ATIVACAO_PRE_VENDAS->ordem(),
        'duracao_dias' => 0,
        'tipo_dias' => 'corridos',
        'visivel' => true,
        'is_ancora' => false,
        'regra_elastica' => true,
    ]);

    // Apenas dep de início, sem dep de fim — fase elástica não tem como resolver.
    CronogramaTemplateFaseDependencia::create([
        'cronograma_template_fase_id' => $mkt->id,
        'depende_de_fase' => FaseCronograma::POSSE,
        'gatilho' => GatilhoTemplateFase::INICIO_ANTERIOR,
        'gap_dias' => 0,
    ]);

    $resultado = app(CronogramaTemplateService::class)
        ->simular($template, CarbonImmutable::parse('2026-08-01'));

    // Comportamento atual: fallback coloca MKT na data da âncora quando elástica não resolve.
    expect($resultado)->toHaveKey(FaseCronograma::MKT_ATIVACAO_PRE_VENDAS->value);
    $mkt = $resultado[FaseCronograma::MKT_ATIVACAO_PRE_VENDAS->value];
    expect($mkt['inicio']->toDateString())->toBe('2026-08-01');
});
