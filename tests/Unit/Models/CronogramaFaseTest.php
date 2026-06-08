<?php

use App\Enums\FaseCronograma;
use App\Enums\StatusCronograma;
use App\Enums\TipoDiasTemplate;
use App\Enums\TipoObraCronograma;
use App\Models\CronogramaFase;
use App\Models\CronogramaTemplate;
use App\Models\CronogramaTemplateFase;
use App\Models\CronogramaTemplateFaseDependencia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->projeto = aplicarSmartFit('2026-08-01');
});

it('regraEfetiva: usa override local quando preenchido', function () {
    $fase = CronogramaFase::where('projeto_id', $this->projeto->id)
        ->where('fase', FaseCronograma::EXECUTIVO)
        ->first();

    $fase->update([
        'regra_duracao_dias' => 45,
        'regra_tipo_dias' => TipoDiasTemplate::UTEIS,
        'regra_customizada' => true,
    ]);

    $regra = $fase->fresh()->regraEfetiva();

    expect($regra->duracao_dias)->toBe(45);
    expect($regra->tipo_dias)->toBe(TipoDiasTemplate::UTEIS);
});

it('regraEfetiva: usa template quando override é null', function () {
    $fase = CronogramaFase::where('projeto_id', $this->projeto->id)
        ->where('fase', FaseCronograma::EXECUTIVO)
        ->first();

    $regra = $fase->regraEfetiva();

    // Template Smart Fit: Executivo = 30d corridos.
    expect($regra->duracao_dias)->toBe(30);
    expect($regra->tipo_dias)->toBe(TipoDiasTemplate::CORRIDOS);
});

it('regraEfetiva: marca elastica=true quando override OR template tem flag', function () {
    $faseMkt = CronogramaFase::where('projeto_id', $this->projeto->id)
        ->where('fase', FaseCronograma::MKT_ATIVACAO_PRE_VENDAS)
        ->first();

    expect($faseMkt->regraEfetiva()->elastica)->toBeTrue();

    $faseObras = CronogramaFase::where('projeto_id', $this->projeto->id)
        ->where('fase', FaseCronograma::OBRAS)
        ->first();

    expect($faseObras->regraEfetiva()->elastica)->toBeFalse();
});

it('isVisivel: respeita override local sobre template', function () {
    // SUFRAMA vem com visivel=false do seeder via templateFase.
    $suframa = CronogramaFase::where('projeto_id', $this->projeto->id)
        ->where('fase', FaseCronograma::SUFRAMA)
        ->first();

    expect($suframa->isVisivel())->toBeFalse();

    $suframa->update(['visivel' => true]);
    expect($suframa->fresh()->isVisivel())->toBeTrue();
});

it('dias_atraso: 0 quando data_prevista_fim no futuro', function () {
    $fase = CronogramaFase::factory()->make([
        'projeto_id' => $this->projeto->id,
        'data_prevista_inicio' => now()->subDay(),
        'data_prevista_fim' => now()->addDays(5),
        'status' => StatusCronograma::EM_ANDAMENTO,
        'data_realizada_fim' => null,
    ]);

    expect($fase->dias_atraso)->toBe(0);
});

it('dias_atraso: positivo quando vencida e não concluída', function () {
    $fase = CronogramaFase::factory()->make([
        'projeto_id' => $this->projeto->id,
        'data_prevista_inicio' => now()->subDays(10),
        'data_prevista_fim' => now()->subDays(3),
        'status' => StatusCronograma::EM_ANDAMENTO,
        'data_realizada_fim' => null,
    ]);

    expect($fase->dias_atraso)->toBe(3);
});

it('dias_atraso: 0 quando concluída mesmo se vencida', function () {
    $fase = CronogramaFase::factory()->make([
        'projeto_id' => $this->projeto->id,
        'data_prevista_inicio' => now()->subDays(10),
        'data_prevista_fim' => now()->subDays(3),
        'status' => StatusCronograma::CONCLUIDO,
        'data_realizada_fim' => now()->subDay(),
    ]);

    expect($fase->dias_atraso)->toBe(0);
});

it('farol: neutro quando concluída', function () {
    $fase = CronogramaFase::factory()->make([
        'data_prevista_fim' => now()->addDays(5),
        'status' => StatusCronograma::CONCLUIDO,
        'percentual_conclusao' => 100,
    ]);

    expect($fase->farol)->toBe('neutro');
});

it('farol: verde quando no prazo e com progresso', function () {
    $fase = CronogramaFase::factory()->make([
        'data_prevista_inicio' => now()->subDay(),
        'data_prevista_fim' => now()->addDays(5),
        'status' => StatusCronograma::EM_ANDAMENTO,
        'percentual_conclusao' => 50,
    ]);

    expect($fase->farol)->toBe('verde');
});

it('farol: vermelho quando atrasada e sem progresso', function () {
    $fase = CronogramaFase::factory()->make([
        'data_prevista_inicio' => now()->subDays(10),
        'data_prevista_fim' => now()->subDays(3),
        'status' => StatusCronograma::EM_ANDAMENTO,
        'percentual_conclusao' => 0,
    ]);

    expect($fase->farol)->toBe('vermelho');
});

it('farol: vermelho quando atraso > 20% da duração planejada', function () {
    // 5 dias de duração, atrasada 3 dias = 60% atraso
    $fase = CronogramaFase::factory()->make([
        'data_prevista_inicio' => now()->subDays(8),
        'data_prevista_fim' => now()->subDays(3),
        'status' => StatusCronograma::EM_ANDAMENTO,
        'percentual_conclusao' => 50,
    ]);

    expect($fase->farol)->toBe('vermelho');
});

it('farol: amarelo quando atraso pequeno e com progresso', function () {
    // 30 dias de duração, atrasada 1 dia = 3% atraso
    $fase = CronogramaFase::factory()->make([
        'data_prevista_inicio' => now()->subDays(31),
        'data_prevista_fim' => now()->subDay(),
        'status' => StatusCronograma::EM_ANDAMENTO,
        'percentual_conclusao' => 80,
    ]);

    expect($fase->farol)->toBe('amarelo');
});

it('label_exibicao: usa titulo_personalizado quando preenchido', function () {
    $fase = CronogramaFase::factory()->make([
        'fase' => FaseCronograma::OBRAS,
        'titulo_personalizado' => 'Obra Customizada XYZ',
    ]);

    expect($fase->label_exibicao)->toBe('Obra Customizada XYZ');
});

it('label_exibicao: usa label do enum quando titulo_personalizado vazio', function () {
    $fase = CronogramaFase::factory()->make([
        'fase' => FaseCronograma::OBRAS,
        'titulo_personalizado' => null,
    ]);

    expect($fase->label_exibicao)->toBe(FaseCronograma::OBRAS->label());
});

it('scope visiveis: inclui visivel=true e exclui visivel=false', function () {
    $visiveis = CronogramaFase::visiveis()->where('projeto_id', $this->projeto->id)->get();

    // SUFRAMA tem visivel=false → não aparece.
    expect($visiveis->pluck('fase')->map->value)
        ->not->toContain(FaseCronograma::SUFRAMA->value);

    // Posse (visível) deve estar.
    expect($visiveis->pluck('fase')->map->value)
        ->toContain(FaseCronograma::POSSE->value);
});

it('scope visiveis: inclui fase com visivel=null se template visivel=true', function () {
    // Template Smart Fit tem todas visíveis exceto SUFRAMA.
    // Quando visivel=null no override, herda do template.
    $fase = CronogramaFase::where('projeto_id', $this->projeto->id)
        ->where('fase', FaseCronograma::OBRAS)
        ->first();

    $fase->update(['visivel' => null]);

    $visiveis = CronogramaFase::visiveis()
        ->where('projeto_id', $this->projeto->id)
        ->pluck('fase')
        ->map->value;

    expect($visiveis)->toContain(FaseCronograma::OBRAS->value);
});

it('casts: fase como FaseCronograma, status como StatusCronograma, regra_tipo_dias como TipoDiasTemplate', function () {
    $fase = CronogramaFase::where('projeto_id', $this->projeto->id)
        ->where('fase', FaseCronograma::EXECUTIVO)
        ->first();

    $fase->update([
        'regra_tipo_dias' => 'uteis',
        'status' => 'em_andamento',
    ]);

    $fase->refresh();

    expect($fase->fase)->toBe(FaseCronograma::EXECUTIVO);
    expect($fase->status)->toBe(StatusCronograma::EM_ANDAMENTO);
    expect($fase->regra_tipo_dias)->toBe(TipoDiasTemplate::UTEIS);
});
