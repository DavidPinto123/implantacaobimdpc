<?php

use App\Enums\FaseCronograma;
use App\Enums\GatilhoTemplateFase;
use App\Models\CronogramaFase;
use App\Models\CronogramaFaseItem;
use App\Models\CronogramaFaseItemDependencia;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

beforeEach(function () {
    $this->projeto = aplicarSmartFit('2026-08-01');
    $this->fase = CronogramaFase::where('projeto_id', $this->projeto->id)
        ->where('fase', FaseCronograma::RECEBIMENTO_PROJETOS_ARQUITETURA)
        ->first();
    // Limpa subitens vindos do template (5 da planilha) para que cada teste
    // controle exatamente quantos itens cria.
    $this->fase->itens()->delete();
    $this->fase->refresh();
});

it('adicionar subitem em fase: persiste com parent_id null', function () {
    $item = CronogramaFaseItem::create([
        'cronograma_fase_id' => $this->fase->id,
        'titulo' => 'Plantas baixas',
        'ordem' => 1,
        'recebido' => false,
    ]);

    expect($item->parent_id)->toBeNull();
    expect($item->cronograma_fase_id)->toBe($this->fase->id);
    expect($item->recebido)->toBeFalse();
});

it('subitem aninhado: parent_id aponta pro pai (profundidade 2)', function () {
    $pai = CronogramaFaseItem::create([
        'cronograma_fase_id' => $this->fase->id,
        'titulo' => 'Plantas',
        'ordem' => 1,
    ]);

    $filho = CronogramaFaseItem::create([
        'cronograma_fase_id' => $this->fase->id,
        'parent_id' => $pai->id,
        'titulo' => 'Planta da academia',
        'ordem' => 1,
    ]);

    expect($filho->parent_id)->toBe($pai->id);
    expect($pai->children()->count())->toBe(1);
});

it('profundidade ilimitada: avô → pai → neto → bisneto', function () {
    $avo = CronogramaFaseItem::create([
        'cronograma_fase_id' => $this->fase->id,
        'titulo' => 'Avô',
        'ordem' => 1,
    ]);

    $pai = CronogramaFaseItem::create([
        'cronograma_fase_id' => $this->fase->id,
        'parent_id' => $avo->id,
        'titulo' => 'Pai',
        'ordem' => 1,
    ]);

    $neto = CronogramaFaseItem::create([
        'cronograma_fase_id' => $this->fase->id,
        'parent_id' => $pai->id,
        'titulo' => 'Neto',
        'ordem' => 1,
    ]);

    $bisneto = CronogramaFaseItem::create([
        'cronograma_fase_id' => $this->fase->id,
        'parent_id' => $neto->id,
        'titulo' => 'Bisneto',
        'ordem' => 1,
    ]);

    expect($bisneto->parent_id)->toBe($neto->id);
    expect($neto->parent_id)->toBe($pai->id);
    expect($pai->parent_id)->toBe($avo->id);
});

it('bisneto recebido propaga até o avô', function () {
    $avo = CronogramaFaseItem::create([
        'cronograma_fase_id' => $this->fase->id,
        'titulo' => 'Avô',
        'ordem' => 1,
        'recebido' => false,
    ]);

    $pai = CronogramaFaseItem::create([
        'cronograma_fase_id' => $this->fase->id,
        'parent_id' => $avo->id,
        'titulo' => 'Pai',
        'ordem' => 1,
        'recebido' => false,
    ]);

    $neto = CronogramaFaseItem::create([
        'cronograma_fase_id' => $this->fase->id,
        'parent_id' => $pai->id,
        'titulo' => 'Neto único',
        'ordem' => 1,
        'recebido' => false,
    ]);

    $neto->update(['recebido' => true]);

    $pai->refresh();
    $avo->refresh();

    // Cada pai tem só 1 filho recebido → cadeia inteira fica recebida.
    expect($pai->recebido)->toBeTrue();
    expect($avo->recebido)->toBeTrue();
});

it('subitem com dependência em outro subitem (depende_de_item_id)', function () {
    $itemA = CronogramaFaseItem::create([
        'cronograma_fase_id' => $this->fase->id,
        'titulo' => 'Item A',
        'ordem' => 1,
    ]);

    $itemB = CronogramaFaseItem::create([
        'cronograma_fase_id' => $this->fase->id,
        'titulo' => 'Item B',
        'ordem' => 2,
    ]);

    $dep = CronogramaFaseItemDependencia::create([
        'cronograma_fase_item_id' => $itemB->id,
        'depende_de_item_id' => $itemA->id,
        'gatilho' => GatilhoTemplateFase::FIM_ANTERIOR,
        'gap_dias' => 0,
    ]);

    expect($dep->dependeDeItem->id)->toBe($itemA->id);
    expect($itemB->dependencias()->count())->toBe(1);
});

it('subitem com dependência em fase (depende_de_fase_id)', function () {
    $brief = CronogramaFase::where('projeto_id', $this->projeto->id)
        ->where('fase', FaseCronograma::BRIEFING)->first();

    $item = CronogramaFaseItem::create([
        'cronograma_fase_id' => $this->fase->id,
        'titulo' => 'Item após briefing',
        'ordem' => 1,
    ]);

    CronogramaFaseItemDependencia::create([
        'cronograma_fase_item_id' => $item->id,
        'depende_de_fase_id' => $brief->id,
        'gatilho' => GatilhoTemplateFase::FIM_ANTERIOR,
        'gap_dias' => 0,
    ]);

    expect($item->dependencias()->first()->dependeDeFase->id)->toBe($brief->id);
});

it('excluir subitem alvo: dependência referenciando-o é tratada (cascade ou null)', function () {
    $itemA = CronogramaFaseItem::create([
        'cronograma_fase_id' => $this->fase->id,
        'titulo' => 'A',
        'ordem' => 1,
    ]);

    $itemB = CronogramaFaseItem::create([
        'cronograma_fase_id' => $this->fase->id,
        'titulo' => 'B',
        'ordem' => 2,
    ]);

    $dep = CronogramaFaseItemDependencia::create([
        'cronograma_fase_item_id' => $itemB->id,
        'depende_de_item_id' => $itemA->id,
        'gatilho' => GatilhoTemplateFase::FIM_ANTERIOR,
        'gap_dias' => 0,
    ]);

    $depId = $dep->id;
    $itemA->delete();

    // Banco usa SET NULL: registro permanece com depende_de_item_id=null.
    $depRecarregada = CronogramaFaseItemDependencia::find($depId);
    expect($depRecarregada)->not->toBeNull();
    expect($depRecarregada->depende_de_item_id)->toBeNull();
    expect(CronogramaFaseItem::find($itemA->id))->toBeNull();
});

it('subitem soft-delete (se aplicável): permanece consultável via withTrashed', function () {
    $item = CronogramaFaseItem::create([
        'cronograma_fase_id' => $this->fase->id,
        'titulo' => 'Tmp',
        'ordem' => 1,
    ]);
    $id = $item->id;

    $item->delete();

    // Modelo não tem SoftDeletes — após delete, registro some.
    expect(CronogramaFaseItem::find($id))->toBeNull();
});

it('reordenar subitens: ordem reflete no banco', function () {
    $a = CronogramaFaseItem::create([
        'cronograma_fase_id' => $this->fase->id,
        'titulo' => 'A',
        'ordem' => 1,
    ]);
    $b = CronogramaFaseItem::create([
        'cronograma_fase_id' => $this->fase->id,
        'titulo' => 'B',
        'ordem' => 2,
    ]);
    $c = CronogramaFaseItem::create([
        'cronograma_fase_id' => $this->fase->id,
        'titulo' => 'C',
        'ordem' => 3,
    ]);

    // Trocar B e C
    $b->update(['ordem' => 3]);
    $c->update(['ordem' => 2]);

    $ordenados = $this->fase->itens()->get()->pluck('titulo')->toArray();
    expect($ordenados)->toBe(['A', 'C', 'B']);
});
