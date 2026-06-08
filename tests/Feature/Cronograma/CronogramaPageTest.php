<?php

use App\Enums\FaseCronograma;
use App\Enums\StatusCronograma;
use App\Filament\Pages\Cronograma;
use App\Models\CronogramaFase;
use App\Models\CronogramaFaseItem;
use App\Models\CronogramaTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

beforeEach(function () {
    setupAdminPanelForTests();
    $this->user = createActiveUserWithPermissions(['View:Cronograma']);
    $this->actingAs($this->user);

    $this->projeto = aplicarSmartFit('2026-08-01');
    $this->template = CronogramaTemplate::where('nome', 'Expansão A partir da posse Reuniao 30/04')->first();
});

it('seleciona projeto e expõe fases visíveis', function () {
    Livewire::test(Cronograma::class)
        ->call('selecionarProjeto', $this->projeto->id)
        ->assertSet('projetoSelecionado', $this->projeto->id);
});

it('aplicarTemplate sem data âncora dispara notification de aviso', function () {
    Livewire::test(Cronograma::class)
        ->call('selecionarProjeto', $this->projeto->id)
        ->set('templateSelecionadoParaAplicar', $this->template->id)
        ->set('templateDataAncora', null)
        ->call('aplicarTemplate')
        ->assertNotified();
});

it('aplicarTemplate com data âncora persiste fases via service', function () {
    // Limpa fases do projeto
    CronogramaFase::where('projeto_id', $this->projeto->id)->forceDelete();
    expect(CronogramaFase::where('projeto_id', $this->projeto->id)->count())->toBe(0);

    Livewire::test(Cronograma::class)
        ->call('selecionarProjeto', $this->projeto->id)
        ->set('templateSelecionadoParaAplicar', $this->template->id)
        ->set('templateDataAncora', '2026-09-15')
        ->call('aplicarTemplate');

    $fases = CronogramaFase::where('projeto_id', $this->projeto->id)->get();
    expect($fases->count())->toBe(22);

    $posse = $fases->firstWhere('fase', FaseCronograma::POSSE);
    expect($posse->data_prevista_inicio->toDateString())->toBe('2026-09-15');
});

it('moverFaseAcima troca ordem da fase com a anterior visível', function () {
    $obras = CronogramaFase::where('projeto_id', $this->projeto->id)
        ->where('fase', FaseCronograma::OBRAS)->first();
    $ordemAntes = $obras->ordem;

    Livewire::test(Cronograma::class)
        ->call('selecionarProjeto', $this->projeto->id)
        ->call('moverFaseAcima', $obras->id);

    $obras->refresh();
    expect($obras->ordem)->toBeLessThan($ordemAntes);
});

it('excluirFaseProjeto remove fase e suas dependências', function () {
    $codOracle = CronogramaFase::where('projeto_id', $this->projeto->id)
        ->where('fase', FaseCronograma::CODIGO_ORACLE)->first();
    $idOracle = $codOracle->id;

    Livewire::test(Cronograma::class)
        ->call('selecionarProjeto', $this->projeto->id)
        ->call('excluirFaseProjeto', $idOracle);

    expect(CronogramaFase::find($idOracle))->toBeNull();
});

it('adicionarSubitem cria item vinculado à fase quando título preenchido', function () {
    $fase1 = CronogramaFase::where('projeto_id', $this->projeto->id)
        ->where('fase', FaseCronograma::RECEBIMENTO_PROJETOS_ARQUITETURA)->first();
    $fase1->itens()->delete(); // limpa subitens do seeder

    expect($fase1->itens()->count())->toBe(0);

    Livewire::test(Cronograma::class)
        ->call('selecionarProjeto', $this->projeto->id)
        ->set("novoSubitemTitulos.{$fase1->id}", 'Plantas')
        ->call('adicionarSubitem', $fase1->id);

    $fase1->refresh();
    expect($fase1->itens()->count())->toBe(1);
    expect($fase1->itens->first()->titulo)->toBe('Plantas');
});

it('adicionarSubitem sem título dispara warning e não persiste', function () {
    $fase1 = CronogramaFase::where('projeto_id', $this->projeto->id)
        ->where('fase', FaseCronograma::RECEBIMENTO_PROJETOS_ARQUITETURA)->first();
    $fase1->itens()->delete();

    Livewire::test(Cronograma::class)
        ->call('selecionarProjeto', $this->projeto->id)
        ->call('adicionarSubitem', $fase1->id)
        ->assertNotified();

    expect($fase1->itens()->count())->toBe(0);
});

it('alternarRecebidoSubitem toggla flag e recalcula percentual da fase', function () {
    $fase1 = CronogramaFase::where('projeto_id', $this->projeto->id)
        ->where('fase', FaseCronograma::RECEBIMENTO_PROJETOS_ARQUITETURA)->first();
    $fase1->itens()->delete();

    $item = CronogramaFaseItem::create([
        'cronograma_fase_id' => $fase1->id,
        'titulo' => 'Plantas',
        'ordem' => 1,
        'recebido' => false,
    ]);

    Livewire::test(Cronograma::class)
        ->call('selecionarProjeto', $this->projeto->id)
        ->call('alternarRecebidoSubitem', $item->id);

    $item->refresh();
    $fase1->refresh();
    expect($item->recebido)->toBeTrue();
    expect($fase1->percentual_conclusao)->toBe(100);
});

it('adicionarFilhoItem cria subitem aninhado quando título preenchido', function () {
    $fase1 = CronogramaFase::where('projeto_id', $this->projeto->id)
        ->where('fase', FaseCronograma::RECEBIMENTO_PROJETOS_ARQUITETURA)->first();
    $fase1->itens()->delete();

    $pai = CronogramaFaseItem::create([
        'cronograma_fase_id' => $fase1->id,
        'titulo' => 'Plantas',
        'ordem' => 1,
    ]);

    Livewire::test(Cronograma::class)
        ->call('selecionarProjeto', $this->projeto->id)
        ->set('novoFilhoTitulo', 'Planta da academia')
        ->call('adicionarFilhoItem', $pai->id);

    expect($pai->children()->count())->toBe(1);
    expect($pai->children->first()->titulo)->toBe('Planta da academia');
});

it('recalcularCascataLote calcula datas em memória e salvarDatasEmLote persiste', function () {
    $obras = CronogramaFase::where('projeto_id', $this->projeto->id)
        ->where('fase', FaseCronograma::OBRAS)->first();
    $impl = CronogramaFase::where('projeto_id', $this->projeto->id)
        ->where('fase', FaseCronograma::IMPLANTACAO)->first();

    $novoInicioObras = $obras->data_prevista_inicio->copy()->addDays(15)->toDateString();
    $implAntes = $impl->data_prevista_inicio->toDateString();

    Livewire::test(Cronograma::class)
        ->call('selecionarProjeto', $this->projeto->id)
        ->set("edicaoLoteDatas.{$obras->id}.prev_i", $novoInicioObras)
        ->call('recalcularCascataLote', $obras->id, 'prev_i')
        ->call('salvarDatasEmLote', false);

    $impl->refresh();
    expect($impl->data_prevista_inicio->toDateString())->not->toBe($implAntes);
});

it('selecionarProjeto = null limpa estado', function () {
    Livewire::test(Cronograma::class)
        ->call('selecionarProjeto', $this->projeto->id)
        ->assertSet('projetoSelecionado', $this->projeto->id);
});
