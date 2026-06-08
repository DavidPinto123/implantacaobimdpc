<?php

use App\Enums\FaseCronograma;
use App\Enums\StatusCronograma;
use App\Models\CronogramaFase;
use App\Models\CronogramaFaseItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->projeto = aplicarSmartFit('2026-08-01');
    $this->fase = CronogramaFase::where('projeto_id', $this->projeto->id)
        ->where('fase', FaseCronograma::RECEBIMENTO_PROJETOS_ARQUITETURA)
        ->first();
    // Limpa subitens vindos do template (5 subitens de planilha) para que cada
    // teste comece com fase vazia e controle exatamente quantos itens cria.
    $this->fase->itens()->delete();
    $this->fase->refresh();
});

it('marcar 1 de 4 subitens recebidos resulta em 25%', function () {
    foreach (range(1, 4) as $i) {
        CronogramaFaseItem::create([
            'cronograma_fase_id' => $this->fase->id,
            'titulo' => "Item $i",
            'recebido' => false,
            'ordem' => $i,
        ]);
    }

    $primeiro = $this->fase->itens()->first();
    $primeiro->update(['recebido' => true]);

    $this->fase->refresh();
    expect($this->fase->percentual_conclusao)->toBe(25);
});

it('todos os subitens recebidos: percentual=100 e fase passa para CONCLUIDO', function () {
    foreach (range(1, 3) as $i) {
        CronogramaFaseItem::create([
            'cronograma_fase_id' => $this->fase->id,
            'titulo' => "Item $i",
            'recebido' => false,
            'ordem' => $i,
        ]);
    }

    $this->fase->itens->each(fn ($i) => $i->update(['recebido' => true]));

    $this->fase->refresh();
    expect($this->fase->percentual_conclusao)->toBe(100);
    expect($this->fase->status)->toBe(StatusCronograma::CONCLUIDO);
});

it('desmarcar subitem em fase 100% reverte status para EM_ANDAMENTO', function () {
    foreach (range(1, 2) as $i) {
        CronogramaFaseItem::create([
            'cronograma_fase_id' => $this->fase->id,
            'titulo' => "Item $i",
            'recebido' => true,
            'ordem' => $i,
        ]);
    }

    $this->fase->refresh();
    expect($this->fase->status)->toBe(StatusCronograma::CONCLUIDO);

    $this->fase->itens->first()->update(['recebido' => false]);

    $this->fase->refresh();
    expect($this->fase->percentual_conclusao)->toBe(50);
    expect($this->fase->status)->toBe(StatusCronograma::EM_ANDAMENTO);
});

it('adicionar quinto subitem reduz percentual proporcionalmente', function () {
    foreach (range(1, 4) as $i) {
        CronogramaFaseItem::create([
            'cronograma_fase_id' => $this->fase->id,
            'titulo' => "Item $i",
            'recebido' => true,
            'ordem' => $i,
        ]);
    }

    $this->fase->refresh();
    expect($this->fase->percentual_conclusao)->toBe(100);

    CronogramaFaseItem::create([
        'cronograma_fase_id' => $this->fase->id,
        'titulo' => 'Item 5',
        'recebido' => false,
        'ordem' => 5,
    ]);

    $this->fase->refresh();
    expect($this->fase->percentual_conclusao)->toBe(80); // 4 de 5
});

it('excluir subitem ajusta percentual', function () {
    foreach (range(1, 4) as $i) {
        CronogramaFaseItem::create([
            'cronograma_fase_id' => $this->fase->id,
            'titulo' => "Item $i",
            'recebido' => $i <= 2,
            'ordem' => $i,
        ]);
    }

    $this->fase->refresh();
    expect($this->fase->percentual_conclusao)->toBe(50); // 2 de 4

    $this->fase->itens()->where('titulo', 'Item 3')->first()->delete();

    $this->fase->refresh();
    expect($this->fase->percentual_conclusao)->toBe(67); // 2 de 3 = 66.67 → 67
});

it('subitem aninhado: pai vira recebido quando todos filhos recebidos', function () {
    $pai = CronogramaFaseItem::create([
        'cronograma_fase_id' => $this->fase->id,
        'titulo' => 'Pai',
        'recebido' => false,
        'ordem' => 1,
    ]);

    $filho1 = CronogramaFaseItem::create([
        'cronograma_fase_id' => $this->fase->id,
        'parent_id' => $pai->id,
        'titulo' => 'Filho 1',
        'recebido' => false,
        'ordem' => 1,
    ]);

    $filho2 = CronogramaFaseItem::create([
        'cronograma_fase_id' => $this->fase->id,
        'parent_id' => $pai->id,
        'titulo' => 'Filho 2',
        'recebido' => false,
        'ordem' => 2,
    ]);

    $filho1->update(['recebido' => true]);
    $filho2->update(['recebido' => true]);

    $pai->refresh();
    expect($pai->recebido)->toBeTrue();
});

it('subitem aninhado: desmarcar 1 filho desmarca o pai', function () {
    $pai = CronogramaFaseItem::create([
        'cronograma_fase_id' => $this->fase->id,
        'titulo' => 'Pai',
        'recebido' => false,
        'ordem' => 1,
    ]);

    $filho1 = CronogramaFaseItem::create([
        'cronograma_fase_id' => $this->fase->id,
        'parent_id' => $pai->id,
        'titulo' => 'Filho 1',
        'recebido' => true,
        'ordem' => 1,
    ]);

    $filho2 = CronogramaFaseItem::create([
        'cronograma_fase_id' => $this->fase->id,
        'parent_id' => $pai->id,
        'titulo' => 'Filho 2',
        'recebido' => true,
        'ordem' => 2,
    ]);

    $pai->refresh();
    expect($pai->recebido)->toBeTrue();

    $filho1->update(['recebido' => false]);

    $pai->refresh();
    expect($pai->recebido)->toBeFalse();
});

it('fase sem subitens permanece em 0%', function () {
    expect($this->fase->itens)->toBeEmpty();

    // forçar trigger do observer
    $item = CronogramaFaseItem::create([
        'cronograma_fase_id' => $this->fase->id,
        'titulo' => 'Tmp',
        'recebido' => false,
        'ordem' => 1,
    ]);
    $item->delete();

    $this->fase->refresh();
    expect($this->fase->percentual_conclusao)->toBe(0);
});
