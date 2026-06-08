<?php

use App\Enums\FaseCronograma;
use App\Enums\GatilhoTemplateFase;
use App\Enums\TipoDiasTemplate;
use App\Enums\TipoObraCronograma;
use App\Models\CronogramaFase;
use App\Models\CronogramaTemplate;
use App\Models\CronogramaTemplateFase;
use App\Models\CronogramaTemplateFaseDependencia;
use App\Models\Projeto;
use App\Services\CronogramaTemplateService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Database\Seeders\CronogramaTemplateSmartFitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('calcula início alinhando o fim com a dependência em dias corridos', function () {
    $service = app(CronogramaTemplateService::class);
    $method = new ReflectionMethod($service, 'resolverInicioPorGatilho');
    $method->setAccessible(true);

    $inicio = $method->invoke(
        $service,
        GatilhoTemplateFase::FIM_JUNTO,
        CarbonImmutable::parse('2026-05-01'),
        CarbonImmutable::parse('2026-05-10'),
        0,
        5,
        TipoDiasTemplate::CORRIDOS,
    );

    expect($inicio->toDateString())->toBe('2026-05-06');
});

it('calcula início alinhando o fim com a dependência em dias úteis', function () {
    $service = app(CronogramaTemplateService::class);
    $method = new ReflectionMethod($service, 'resolverInicioPorGatilho');
    $method->setAccessible(true);

    $inicio = $method->invoke(
        $service,
        GatilhoTemplateFase::FIM_JUNTO,
        CarbonImmutable::parse('2026-05-01'),
        CarbonImmutable::parse('2026-05-08'),
        1,
        3,
        TipoDiasTemplate::UTEIS,
    );

    expect($inicio->toDateString())->toBe('2026-05-07');
});

it('fase elástica: início e fim emergem de Obras e Inauguração (MKT)', function () {
    $service = app(CronogramaTemplateService::class);
    $posse = 'posse';
    $obras = FaseCronograma::OBRAS->value;
    $impl = FaseCronograma::IMPLANTACAO->value;
    $inau = FaseCronograma::INAUGURACAO->value;
    $mkt = FaseCronograma::MKT_ATIVACAO_PRE_VENDAS->value;

    $duracoes = [
        $posse => 0,
        $obras => 5,
        $impl => 2,
        $inau => 0,
        $mkt => 0,
    ];
    $tipo = array_fill_keys(array_keys($duracoes), TipoDiasTemplate::CORRIDOS);
    $elasticas = [
        $posse => false,
        $obras => false,
        $impl => false,
        $inau => false,
        $mkt => true,
    ];
    $deps = [
        $obras => [
            (object) ['de' => $posse, 'gatilho' => GatilhoTemplateFase::FIM_ANTERIOR, 'gap' => 0],
        ],
        $impl => [
            (object) ['de' => $obras, 'gatilho' => GatilhoTemplateFase::FIM_ANTERIOR, 'gap' => 0],
        ],
        $inau => [
            (object) ['de' => $impl, 'gatilho' => GatilhoTemplateFase::FIM_ANTERIOR_MESMO_DIA, 'gap' => 0],
        ],
        $mkt => [
            (object) ['de' => $obras, 'gatilho' => GatilhoTemplateFase::INICIO_ANTERIOR, 'gap' => 0],
            (object) ['de' => $inau, 'gatilho' => GatilhoTemplateFase::FIM_JUNTO, 'gap' => 0],
        ],
    ];

    $out = $service->calcularDatasFromMaps(
        $posse,
        CarbonImmutable::parse('2026-01-01'),
        $duracoes,
        $tipo,
        $deps,
        null,
        $elasticas,
    );

    expect($out[$mkt]['inicio']->toDateString())->toBe($out[$obras]['inicio']->toDateString());
    expect($out[$mkt]['fim']->toDateString())->toBe($out[$inau]['fim']->toDateString());
});

it('FIM_ANTES_INICIO em marco backward: termina 45d antes do início da fase forward', function () {
    $service = app(CronogramaTemplateService::class);
    $posse = FaseCronograma::POSSE->value;
    $obras = FaseCronograma::OBRAS->value;
    $impl = FaseCronograma::IMPLANTACAO->value;
    // Usa o enum legado PIN_SUFRAMA só como string-chave do grafo (o template
    // oficial não usa mais essa fase, mas o caso de cálculo continua válido).
    $pin = FaseCronograma::PIN_SUFRAMA->value;

    $duracoes = [
        $posse => 0,
        $obras => 10,
        $impl => 5,
        $pin => 0,
    ];
    $tipo = array_fill_keys(array_keys($duracoes), TipoDiasTemplate::CORRIDOS);
    $deps = [
        $obras => [
            (object) ['de' => $posse, 'gatilho' => GatilhoTemplateFase::FIM_ANTERIOR, 'gap' => 0],
        ],
        $impl => [
            (object) ['de' => $obras, 'gatilho' => GatilhoTemplateFase::FIM_ANTERIOR, 'gap' => 0],
        ],
        $pin => [
            (object) ['de' => $impl, 'gatilho' => GatilhoTemplateFase::FIM_ANTES_INICIO, 'gap' => -45],
        ],
    ];

    $out = $service->calcularDatasFromMaps(
        $posse,
        CarbonImmutable::parse('2026-06-01'),
        $duracoes,
        $tipo,
        $deps,
    );

    $esperadoPin = $out[$impl]['inicio']->addDays(-45);
    expect($out[$pin]['fim']->toDateString())->toBe($esperadoPin->toDateString());
    expect($out[$pin]['inicio']->toDateString())->toBe($esperadoPin->toDateString());
});

it('Fase elástica com FIM_ANTES_INICIO: inicia em raiz, fim = consumidor.inicio − 1', function () {
    $service = app(CronogramaTemplateService::class);
    $posse = FaseCronograma::POSSE->value;
    $inicio = FaseCronograma::INICIO_PROJETO->value;
    $lev = FaseCronograma::LEVANTAMENTO_CADASTRAL->value;
    $brief = FaseCronograma::BRIEFING->value;
    $fase1 = FaseCronograma::RECEBIMENTO_PROJETOS_ARQUITETURA->value;

    $duracoes = [
        $posse => 0,
        $inicio => 0,
        $lev => 15,
        $brief => 1,
        $fase1 => 0,
    ];
    $tipo = array_fill_keys(array_keys($duracoes), TipoDiasTemplate::CORRIDOS);
    $elasticas = [
        $posse => false, $inicio => false, $lev => false, $brief => false,
        $fase1 => true,
    ];
    $deps = [
        $inicio => [],
        $lev => [
            (object) ['de' => $inicio, 'gatilho' => GatilhoTemplateFase::INICIO_ANTERIOR, 'gap' => 0],
        ],
        $brief => [
            (object) ['de' => $lev, 'gatilho' => GatilhoTemplateFase::FIM_ANTERIOR, 'gap' => 0],
        ],
        $fase1 => [
            (object) ['de' => $inicio, 'gatilho' => GatilhoTemplateFase::INICIO_ANTERIOR, 'gap' => 0],
            (object) ['de' => $brief, 'gatilho' => GatilhoTemplateFase::FIM_ANTES_INICIO, 'gap' => -1],
        ],
    ];

    $out = $service->calcularDatasFromMaps(
        $posse,
        CarbonImmutable::parse('2026-08-01'),
        $duracoes,
        $tipo,
        $deps,
        null,
        $elasticas,
    );

    expect($out[$fase1]['inicio']->toDateString())->toBe($out[$inicio]['inicio']->toDateString());
    expect($out[$fase1]['fim']->toDateString())->toBe($out[$brief]['inicio']->subDay()->toDateString());
});

it('retroplanejamento: subgrafo sem ligação à âncora recebe shift até encostar no dia anterior à Posse', function () {
    Carbon::setTestNow('2026-01-01');

    try {
        $service = app(CronogramaTemplateService::class);
        $posse = FaseCronograma::POSSE->value;
        $pre = FaseCronograma::ASSINATURA_CONTRATO->value;
        $floater = FaseCronograma::INICIO_PROJETO->value;

        $duracoes = [
            $posse => 0,
            $pre => 1,
            $floater => 0,
        ];
        $tipo = array_fill_keys(array_keys($duracoes), TipoDiasTemplate::CORRIDOS);
        $deps = [
            $pre => [
                (object) ['de' => $floater, 'gatilho' => GatilhoTemplateFase::FIM_ANTERIOR, 'gap' => 0],
            ],
            $floater => [],
        ];

        $dataPosse = CarbonImmutable::parse('2026-06-01');
        $out = $service->calcularDatasFromMaps(
            $posse,
            $dataPosse,
            $duracoes,
            $tipo,
            $deps,
        );

        expect($out[$floater]['inicio']->lessThan($dataPosse))->toBeTrue();
        expect($out[$pre]['fim']->toDateString())->toBe($dataPosse->subDay()->toDateString());
    } finally {
        Carbon::setTestNow();
    }
});

it('aplicar template persiste fase com visivel=false', function () {
    $template = CronogramaTemplate::create([
        'nome' => 'Teste Unit Visível',
        'tipo_obra' => TipoObraCronograma::EXPANSAO,
        'ancora_campo' => 'projeto.data_posse',
        'ativo' => true,
    ]);

    $fPosse = CronogramaTemplateFase::create([
        'cronograma_template_id' => $template->id,
        'fase' => FaseCronograma::POSSE,
        'ordem' => FaseCronograma::POSSE->ordem(),
        'duracao_dias' => 0,
        'tipo_dias' => TipoDiasTemplate::CORRIDOS,
        'visivel' => true,
        'is_ancora' => true,
        'regra_elastica' => false,
    ]);

    $fHidden = CronogramaTemplateFase::create([
        'cronograma_template_id' => $template->id,
        'fase' => FaseCronograma::SUFRAMA,
        'ordem' => FaseCronograma::SUFRAMA->ordem(),
        'duracao_dias' => 1,
        'tipo_dias' => TipoDiasTemplate::CORRIDOS,
        'visivel' => false,
        'is_ancora' => false,
        'regra_elastica' => false,
    ]);

    CronogramaTemplateFaseDependencia::create([
        'cronograma_template_fase_id' => $fHidden->id,
        'depende_de_fase' => FaseCronograma::POSSE,
        'gatilho' => GatilhoTemplateFase::FIM_ANTERIOR,
        'gap_dias' => 0,
    ]);

    $projeto = Projeto::factory()->create(['data_posse' => '2026-03-10']);

    app(CronogramaTemplateService::class)->aplicar($template, $projeto);

    $faseObra = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::SUFRAMA)
        ->first();

    expect($faseObra)->not->toBeNull();
    expect($faseObra->visivel)->toBeFalse();
});

it('seeder Smart Fit: 22 fases, MKT elástico de Início do Projeto até Inauguração', function () {
    (new CronogramaTemplateSmartFitSeeder)->run();

    $template = CronogramaTemplate::where('nome', 'Expansão A partir da posse Reuniao 30/04')->firstOrFail();
    expect($template->fases)->toHaveCount(22);

    $service = app(CronogramaTemplateService::class);
    $sim = $service->simular($template, CarbonImmutable::parse('2026-08-01'));

    $inicio = FaseCronograma::INICIO_PROJETO->value;
    $mkt = FaseCronograma::MKT_ATIVACAO_PRE_VENDAS->value;
    $inau = FaseCronograma::INAUGURACAO->value;
    $posse = FaseCronograma::POSSE->value;

    expect($sim[$mkt]['inicio']->toDateString())->toBe($sim[$inicio]['inicio']->toDateString());
    expect($sim[$mkt]['fim']->toDateString())->toBe($sim[$inau]['fim']->toDateString());
    expect($sim[$inau]['fim']->greaterThan($sim[$posse]['inicio']))->toBeTrue();
});

it('seeder Smart Fit: Orçamentos termina 1 dia antes da Posse', function () {
    (new CronogramaTemplateSmartFitSeeder)->run();
    $template = CronogramaTemplate::where('nome', 'Expansão A partir da posse Reuniao 30/04')->firstOrFail();
    $sim = app(CronogramaTemplateService::class)->simular($template, CarbonImmutable::parse('2026-08-01'));

    $posse = $sim[FaseCronograma::POSSE->value]['inicio'];
    $orcFim = $sim[FaseCronograma::ORCAMENTOS->value]['fim'];

    expect($orcFim->toDateString())->toBe($posse->subDay()->toDateString());
});

it('seeder Smart Fit: Obras espera Posse+61 (1d+gordura 60d) E Prazo Legal+1 (dep dupla AND)', function () {
    (new CronogramaTemplateSmartFitSeeder)->run();
    $template = CronogramaTemplate::where('nome', 'Expansão A partir da posse Reuniao 30/04')->firstOrFail();
    $sim = app(CronogramaTemplateService::class)->simular($template, CarbonImmutable::parse('2026-08-01'));

    $obrasInicio = $sim[FaseCronograma::OBRAS->value]['inicio'];
    $posse = $sim[FaseCronograma::POSSE->value]['inicio'];
    $prazoFim = $sim[FaseCronograma::PRAZO_LEGAL->value]['fim'];

    // Posse + 61 dias (1 dia natural + 60 dias de gordura) ou Prazo Legal + 1, o que vier depois.
    $candidatoPosse = $posse->addDays(61);
    $candidatoPrazo = $prazoFim->addDay();
    $minObras = $candidatoPosse->greaterThan($candidatoPrazo) ? $candidatoPosse : $candidatoPrazo;
    expect($obrasInicio->toDateString())->toBe($minObras->toDateString());
});

it('seeder Smart Fit: Recebimento Arquitetura/Complementares são elásticas com fim no consumidor−1', function () {
    (new CronogramaTemplateSmartFitSeeder)->run();
    $template = CronogramaTemplate::where('nome', 'Expansão A partir da posse Reuniao 30/04')->firstOrFail();
    $sim = app(CronogramaTemplateService::class)->simular($template, CarbonImmutable::parse('2026-08-01'));

    $iniProj = $sim[FaseCronograma::INICIO_PROJETO->value]['inicio'];
    $brief = $sim[FaseCronograma::BRIEFING->value]['inicio'];
    $start = $sim[FaseCronograma::START_PROJETOS_EXECUTIVOS->value]['inicio'];
    $fase1 = $sim[FaseCronograma::RECEBIMENTO_PROJETOS_ARQUITETURA->value];
    $fase2 = $sim[FaseCronograma::RECEBIMENTO_PROJETOS_COMPLEMENTARES->value];

    expect($fase1['inicio']->toDateString())->toBe($iniProj->toDateString());
    expect($fase1['fim']->toDateString())->toBe($brief->subDay()->toDateString());
    expect($fase2['inicio']->toDateString())->toBe($iniProj->toDateString());
    expect($fase2['fim']->toDateString())->toBe($start->subDay()->toDateString());
});

it('seeder Smart Fit: Recebimento Arquitetura tem 5 subitens da planilha', function () {
    (new CronogramaTemplateSmartFitSeeder)->run();
    $template = CronogramaTemplate::where('nome', 'Expansão A partir da posse Reuniao 30/04')->firstOrFail();

    $fase1 = $template->fases->firstWhere('fase', FaseCronograma::RECEBIMENTO_PROJETOS_ARQUITETURA);
    $titulos = $fase1->itens->sortBy('ordem')->pluck('titulo')->all();

    expect($titulos)->toBe([
        'Plantas',
        'Local da academia indicado?',
        'Cortes',
        'Fachadas',
        'Posição da área técnica externa definida?',
    ]);
});

it('seeder Smart Fit: Recebimento Complementares tem 7 subitens da planilha', function () {
    (new CronogramaTemplateSmartFitSeeder)->run();
    $template = CronogramaTemplate::where('nome', 'Expansão A partir da posse Reuniao 30/04')->firstOrFail();

    $fase2 = $template->fases->firstWhere('fase', FaseCronograma::RECEBIMENTO_PROJETOS_COMPLEMENTARES);
    $titulos = $fase2->itens->sortBy('ordem')->pluck('titulo')->all();

    expect($titulos)->toBe([
        'Entrada de Energia',
        'Estrutura',
        'Estrutura Cobertura / Cobertura',
        'Águas Pluviais',
        'Elétrica',
        'Hidráulica',
        'Incêndio',
    ]);
});

it('seeder Smart Fit: SUFRAMA é fase pai oculta com 3 subitens (CNPJ, PIN, Compras)', function () {
    (new CronogramaTemplateSmartFitSeeder)->run();
    $template = CronogramaTemplate::where('nome', 'Expansão A partir da posse Reuniao 30/04')->firstOrFail();

    $suframa = $template->fases->firstWhere('fase', FaseCronograma::SUFRAMA);
    expect($suframa)->not->toBeNull();
    expect((bool) $suframa->visivel)->toBeFalse();

    $titulos = $suframa->itens->pluck('titulo')->all();
    expect($titulos)
        ->toContain('CNPJ Suframa')
        ->toContain('PIN Suframa')
        ->toContain('Compras Suframa');

    // PIN tem dep FIM_ANTES_INICIO com IMPLANTACAO (gap=-45).
    $pin = $suframa->itens->firstWhere('titulo', 'PIN Suframa');
    $depPin = $pin->dependencias->first();
    expect($depPin->gatilho)->toBe(GatilhoTemplateFase::FIM_ANTES_INICIO);
    expect((int) $depPin->gap_dias)->toBe(-45);
});

it('aumentar duração do Prazo Legal empurra Obras (e descendentes)', function () {
    (new CronogramaTemplateSmartFitSeeder)->run();
    $template = CronogramaTemplate::where('nome', 'Expansão A partir da posse Reuniao 30/04')->firstOrFail();
    $service = app(CronogramaTemplateService::class);

    $simBase = $service->simular($template, CarbonImmutable::parse('2026-08-01'));

    // Aumenta duração do prazo legal de 60 para 120 dias diretamente no template
    $tplPrazo = $template->fases()->where('fase', FaseCronograma::PRAZO_LEGAL)->first();
    $tplPrazo->update(['duracao_dias' => 120]);
    $template->refresh();

    $simAlt = $service->simular($template, CarbonImmutable::parse('2026-08-01'));

    expect($simAlt[FaseCronograma::OBRAS->value]['inicio']
        ->greaterThan($simBase[FaseCronograma::OBRAS->value]['inicio']))->toBeTrue();
    expect($simAlt[FaseCronograma::INAUGURACAO->value]['fim']
        ->greaterThan($simBase[FaseCronograma::INAUGURACAO->value]['fim']))->toBeTrue();
});

it('aumentar duração de fase backward (Levantamento) recua INICIO_PROJETO mantendo Posse fixa', function () {
    (new CronogramaTemplateSmartFitSeeder)->run();
    $template = CronogramaTemplate::where('nome', 'Expansão A partir da posse Reuniao 30/04')->firstOrFail();
    $service = app(CronogramaTemplateService::class);

    $simBase = $service->simular($template, CarbonImmutable::parse('2026-08-01'));

    $tplLev = $template->fases()->where('fase', FaseCronograma::LEVANTAMENTO_CADASTRAL)->first();
    $tplLev->update(['duracao_dias' => 25]);
    $template->refresh();

    $simAlt = $service->simular($template, CarbonImmutable::parse('2026-08-01'));

    // Posse permanece fixa.
    expect($simAlt[FaseCronograma::POSSE->value]['inicio']->toDateString())
        ->toBe($simBase[FaseCronograma::POSSE->value]['inicio']->toDateString());

    // No retroplanejamento, aumentar fase backward recua o INICIO_PROJETO (cronograma "começa antes").
    expect($simAlt[FaseCronograma::INICIO_PROJETO->value]['inicio']
        ->lessThan($simBase[FaseCronograma::INICIO_PROJETO->value]['inicio']))->toBeTrue();

    // Fase 1 acompanha o novo INICIO_PROJETO (fim continua em briefing.inicio − 1).
    $fase1Alt = $simAlt[FaseCronograma::RECEBIMENTO_PROJETOS_ARQUITETURA->value];
    $briefAlt = $simAlt[FaseCronograma::BRIEFING->value];
    expect($fase1Alt['fim']->toDateString())->toBe($briefAlt['inicio']->subDay()->toDateString());
});

it('mudar data_posse desloca todo o cronograma proporcionalmente', function () {
    (new CronogramaTemplateSmartFitSeeder)->run();
    $template = CronogramaTemplate::where('nome', 'Expansão A partir da posse Reuniao 30/04')->firstOrFail();
    $service = app(CronogramaTemplateService::class);

    $simA = $service->simular($template, CarbonImmutable::parse('2026-08-01'));
    $simB = $service->simular($template, CarbonImmutable::parse('2026-08-31'));

    // Todas as fases deslocam exatamente 30 dias.
    foreach ($simA as $fase => $datasA) {
        $datasB = $simB[$fase];
        expect((int) $datasA['inicio']->diffInDays($datasB['inicio'], absolute: true))->toBe(30);
        expect((int) $datasA['fim']->diffInDays($datasB['fim'], absolute: true))->toBe(30);
    }
});

it('extrairRegrasEfetivas: override local prevalece sobre template', function () {
    (new CronogramaTemplateSmartFitSeeder)->run();
    $template = CronogramaTemplate::where('nome', 'Expansão A partir da posse Reuniao 30/04')->firstOrFail();

    $projeto = Projeto::factory()->create(['data_posse' => '2026-08-01']);
    app(CronogramaTemplateService::class)->aplicar($template, $projeto);

    $exec = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::EXECUTIVO)
        ->first();

    $exec->update([
        'regra_duracao_dias' => 60,
        'regra_tipo_dias' => TipoDiasTemplate::UTEIS,
        'regra_customizada' => true,
    ]);

    $fasesObra = CronogramaFase::where('projeto_id', $projeto->id)->get()
        ->keyBy(fn ($f) => $f->fase->value);

    [$duracoes, $tipoDias, $deps, $elasticas] = app(CronogramaTemplateService::class)
        ->extrairRegrasEfetivas($fasesObra);

    expect($duracoes[FaseCronograma::EXECUTIVO->value])->toBe(60);
    expect($tipoDias[FaseCronograma::EXECUTIVO->value])->toBe(TipoDiasTemplate::UTEIS);
});

it('resolverAncora retorna data_posse quando preenchido', function () {
    (new CronogramaTemplateSmartFitSeeder)->run();
    $template = CronogramaTemplate::where('nome', 'Expansão A partir da posse Reuniao 30/04')->firstOrFail();
    $projeto = Projeto::factory()->create(['data_posse' => '2026-08-01']);

    $ancora = app(CronogramaTemplateService::class)->resolverAncora($template, $projeto);

    expect($ancora)->not->toBeNull();
    expect($ancora->toDateString())->toBe('2026-08-01');
});

it('resolverAncora retorna null quando data_posse não preenchido (sem fallback)', function () {
    (new CronogramaTemplateSmartFitSeeder)->run();
    $template = CronogramaTemplate::where('nome', 'Expansão A partir da posse Reuniao 30/04')->firstOrFail();
    $projeto = Projeto::factory()->create(['data_posse' => null]);

    $ancora = app(CronogramaTemplateService::class)->resolverAncora($template, $projeto);

    expect($ancora)->toBeNull();
});

it('dependentesVisiveis lista fases que dependem direta ou transitivamente da alvo', function () {
    (new CronogramaTemplateSmartFitSeeder)->run();
    $template = CronogramaTemplate::where('nome', 'Expansão A partir da posse Reuniao 30/04')->firstOrFail();
    $projeto = Projeto::factory()->create(['data_posse' => '2026-08-01']);
    app(CronogramaTemplateService::class)->aplicar($template, $projeto);

    $dependentes = app(CronogramaTemplateService::class)
        ->dependentesVisiveis(FaseCronograma::OBRAS, $projeto->id);

    $values = $dependentes->pluck('fase')->map->value->all();
    expect($values)->toContain(FaseCronograma::IMPLANTACAO->value);
});
