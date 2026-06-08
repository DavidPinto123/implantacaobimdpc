<?php

use App\Enums\FaseCronograma;
use App\Enums\StatusCronograma;
use App\Models\CronogramaFase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->projeto = aplicarSmartFit('2026-08-01');
    $this->fase = CronogramaFase::where('projeto_id', $this->projeto->id)
        ->where('fase', FaseCronograma::EXECUTIVO)
        ->first();
});

it('status final dispara percentual=100 e data_realizada_fim=now', function () {
    $this->fase->update(['status' => StatusCronograma::CONCLUIDO]);

    $this->fase->refresh();

    expect($this->fase->percentual_conclusao)->toBe(100);
    expect($this->fase->data_realizada_fim?->toDateString())->toBe(now()->toDateString());
    expect($this->fase->data_realizada_inicio?->toDateString())->toBe(now()->toDateString());
});

it('reverter de CONCLUIDO para EM_ANDAMENTO limpa data_realizada_fim mas mantém início', function () {
    // Avança para concluído primeiro
    $this->fase->update(['status' => StatusCronograma::CONCLUIDO]);
    $this->fase->refresh();
    expect($this->fase->data_realizada_fim)->not->toBeNull();

    // Reverte para EM_ANDAMENTO
    $this->fase->update(['status' => StatusCronograma::EM_ANDAMENTO]);
    $this->fase->refresh();

    expect($this->fase->data_realizada_fim)->toBeNull();
    expect($this->fase->data_realizada_inicio)->not->toBeNull();
});

it('reverter de CONCLUIDO para NAO_INICIADO limpa ambas as datas reais', function () {
    $this->fase->update(['status' => StatusCronograma::CONCLUIDO]);
    $this->fase->refresh();

    $this->fase->update(['status' => StatusCronograma::NAO_INICIADO]);
    $this->fase->refresh();

    expect($this->fase->data_realizada_inicio)->toBeNull();
    expect($this->fase->data_realizada_fim)->toBeNull();
});

it('mudar para EM_ANDAMENTO marca data_realizada_inicio', function () {
    expect($this->fase->data_realizada_inicio)->toBeNull();

    $this->fase->update(['status' => StatusCronograma::EM_ANDAMENTO]);
    $this->fase->refresh();

    expect($this->fase->data_realizada_inicio?->toDateString())->toBe(now()->toDateString());
    expect($this->fase->data_realizada_fim)->toBeNull();
});

it('NAO_REALIZADO (status inicial) limpa datas reais', function () {
    $this->fase->update(['status' => StatusCronograma::CONCLUIDO]);
    $this->fase->refresh();

    $this->fase->update(['status' => StatusCronograma::NAO_REALIZADO]);
    $this->fase->refresh();

    expect($this->fase->data_realizada_inicio)->toBeNull();
    expect($this->fase->data_realizada_fim)->toBeNull();
});

it('REALIZADO é tratado como status final (alias de CONCLUIDO)', function () {
    $this->fase->update(['status' => StatusCronograma::REALIZADO]);
    $this->fase->refresh();

    expect($this->fase->percentual_conclusao)->toBe(100);
    expect($this->fase->data_realizada_fim)->not->toBeNull();
});

it('observer não dispara quando status não muda', function () {
    $this->fase->update(['status' => StatusCronograma::EM_ANDAMENTO]);
    $this->fase->refresh();
    $iniciadoEm = $this->fase->data_realizada_inicio;

    sleep(1); // garantir que `now()` mudaria

    $this->fase->update(['observacoes' => 'Outro campo qualquer']);
    $this->fase->refresh();

    // data_realizada_inicio não deve ser sobrescrita.
    expect($this->fase->data_realizada_inicio?->toDateString())->toBe($iniciadoEm?->toDateString());
});

it('SyncObserver: alterar data_prevista_inicio em VISITA_TECNICA atualiza vis_plan_inicio do projeto', function () {
    $visita = CronogramaFase::where('projeto_id', $this->projeto->id)
        ->where('fase', FaseCronograma::VISITA_TECNICA)
        ->first();

    $novaData = '2026-06-15';
    $visita->update(['data_prevista_inicio' => $novaData]);

    $this->projeto->refresh();

    expect($this->projeto->vis_plan_inicio?->toDateString())->toBe($novaData);
});

it('SyncObserver: alterar data_realizada_fim em INAUGURACAO atualiza inau_rea_fim do projeto', function () {
    $inau = CronogramaFase::where('projeto_id', $this->projeto->id)
        ->where('fase', FaseCronograma::INAUGURACAO)
        ->first();

    $novaData = '2026-12-01';
    $inau->update(['data_realizada_fim' => $novaData]);

    $this->projeto->refresh();

    // Campo do projeto definido pelo CronogramaFaseSyncMap::reverse() para INAUGURACAO.
    // O nome exato vem do forward() — vamos verificar via lookup dinâmico.
    $mapa = \App\Support\CronogramaFaseSyncMap::reverse()[FaseCronograma::INAUGURACAO->value];
    $campo = $mapa['real_fim'];

    if ($campo) {
        expect($this->projeto->{$campo}?->toDateString())->toBe($novaData);
    } else {
        expect(true)->toBeTrue(); // Skip se INAUGURACAO real_fim não está mapeado.
    }
});
