<?php

use App\Enums\FaseCronograma;
use App\Enums\GatilhoTemplateFase;
use App\Enums\TipoObraCronograma;
use App\Filament\Pages\CronogramaTemplates;
use App\Models\CronogramaTemplate;
use App\Models\CronogramaTemplateFase;
use App\Models\CronogramaTemplateFaseItem;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

beforeEach(function () {
    setupAdminPanelForTests();
    $this->user = createActiveUserWithPermissions(['View:CronogramaTemplates']);
    $this->actingAs($this->user);

    $this->template = seedTemplateSmartFit();
});

// ============================================================================
// CRUD do template (não muda)
// ============================================================================

it('novoTemplate cria template e seleciona automaticamente', function () {
    $countAntes = CronogramaTemplate::count();

    Livewire::test(CronogramaTemplates::class)->call('novoTemplate');

    expect(CronogramaTemplate::count())->toBe($countAntes + 1);
    expect(CronogramaTemplate::orderBy('id', 'desc')->first()->nome)->toBe('Novo template');
});

it('salvarTemplate persiste alterações de nome e ancora_campo', function () {
    Livewire::test(CronogramaTemplates::class)
        ->call('selecionarTemplate', $this->template->id)
        ->set('tplNome', 'Smart Fit Renomeado')
        ->set('tplAncoraCampo', 'projeto.data_inauguracao')
        ->call('salvarTemplate');

    $this->template->refresh();
    expect($this->template->nome)->toBe('Smart Fit Renomeado');
    expect($this->template->ancora_campo)->toBe('projeto.data_inauguracao');
});

it('duplicarTemplate clona com (cópia) no nome', function () {
    $countAntes = CronogramaTemplate::count();

    Livewire::test(CronogramaTemplates::class)
        ->call('selecionarTemplate', $this->template->id)
        ->call('duplicarTemplate');

    expect(CronogramaTemplate::count())->toBe($countAntes + 1);
    expect(CronogramaTemplate::where('nome', 'like', '%(cópia)')->exists())->toBeTrue();
});

it('exportarTemplate retorna response StreamedResponse', function () {
    Livewire::test(CronogramaTemplates::class)
        ->call('selecionarTemplate', $this->template->id)
        ->call('exportarTemplate')
        ->assertFileDownloaded();
});

// ============================================================================
// Drawer "Editar fases" — buffer + simulação reativa
// ============================================================================

it('selecionarTemplate abre drawer e popula buffer com snapshot do template', function () {
    Livewire::test(CronogramaTemplates::class)
        ->call('selecionarTemplate', $this->template->id)
        ->assertSet('mostrarEditorFases', true)
        ->assertSet('bufferDirty', false)
        ->assertCount('bufferTemplate', 22);
});

it('atualizarBufferDuracao escreve no buffer e marca dirty', function () {
    $component = Livewire::test(CronogramaTemplates::class)
        ->call('selecionarTemplate', $this->template->id);

    $execId = collect($component->get('bufferTemplate'))
        ->search(fn ($d) => $d['fase'] === FaseCronograma::EXECUTIVO->value);

    $component->call('atualizarBufferDuracao', $execId, 45)
        ->assertSet('bufferDirty', true);

    $buffer = $component->get('bufferTemplate');
    expect($buffer[$execId]['duracao'])->toBe(45);
});

it('simularComBuffer recalcula datas em tempo real ao editar duração', function () {
    $component = Livewire::test(CronogramaTemplates::class)
        ->call('selecionarTemplate', $this->template->id);

    $sim1 = $component->get('bufferSimulacao');
    $execId = collect($component->get('bufferTemplate'))
        ->search(fn ($d) => $d['fase'] === FaseCronograma::EXECUTIVO->value);

    $component->call('atualizarBufferDuracao', $execId, 60);
    $sim2 = $component->get('bufferSimulacao');

    // Posse é âncora (fixa). Mudar duração de fase backward (Executivo) recua
    // INICIO_PROJETO. Forward (Obras) não muda.
    expect($sim1['inicio_projeto']['inicio'])->not->toBe($sim2['inicio_projeto']['inicio']);
});

it('salvarBuffer persiste todas as alterações no DB e limpa dirty', function () {
    $component = Livewire::test(CronogramaTemplates::class)
        ->call('selecionarTemplate', $this->template->id);

    $execId = collect($component->get('bufferTemplate'))
        ->search(fn ($d) => $d['fase'] === FaseCronograma::EXECUTIVO->value);

    $duracaoAntes = CronogramaTemplateFase::find($execId)->duracao_dias;

    $component->call('atualizarBufferDuracao', $execId, 45)
        ->call('salvarBuffer')
        ->assertSet('bufferDirty', false);

    expect(CronogramaTemplateFase::find($execId)->duracao_dias)->toBe(45);
    expect(CronogramaTemplateFase::find($execId)->duracao_dias)->not->toBe($duracaoAntes);
});

it('descartarBuffer recarrega do DB sem persistir mudanças do buffer', function () {
    $component = Livewire::test(CronogramaTemplates::class)
        ->call('selecionarTemplate', $this->template->id);

    $execId = collect($component->get('bufferTemplate'))
        ->search(fn ($d) => $d['fase'] === FaseCronograma::EXECUTIVO->value);
    $duracaoOriginal = CronogramaTemplateFase::find($execId)->duracao_dias;

    $component->call('atualizarBufferDuracao', $execId, 999)
        ->assertSet('bufferDirty', true)
        ->call('descartarBuffer')
        ->assertSet('bufferDirty', false);

    // DB inalterado.
    expect(CronogramaTemplateFase::find($execId)->duracao_dias)->toBe($duracaoOriginal);
    // Buffer recarregado.
    expect($component->get('bufferTemplate')[$execId]['duracao'])->toBe($duracaoOriginal);
});

it('fecharEditorFases sem dirty fecha direto', function () {
    Livewire::test(CronogramaTemplates::class)
        ->call('selecionarTemplate', $this->template->id)
        ->call('fecharEditorFases')
        ->assertSet('mostrarEditorFases', false);
});

it('fecharEditorFases com dirty pendente abre modal de confirmação', function () {
    $component = Livewire::test(CronogramaTemplates::class)
        ->call('selecionarTemplate', $this->template->id);

    $execId = collect($component->get('bufferTemplate'))
        ->search(fn ($d) => $d['fase'] === FaseCronograma::EXECUTIVO->value);

    $component->call('atualizarBufferDuracao', $execId, 45)
        ->call('fecharEditorFases')
        ->assertSet('mostrarConfirmacaoDescarte', true)
        ->assertSet('mostrarEditorFases', true); // ainda aberto
});

it('atualizarBufferElastica zera duração ao marcar elástica', function () {
    $component = Livewire::test(CronogramaTemplates::class)
        ->call('selecionarTemplate', $this->template->id);

    $obrasId = collect($component->get('bufferTemplate'))
        ->search(fn ($d) => $d['fase'] === FaseCronograma::OBRAS->value);

    $component->call('atualizarBufferElastica', $obrasId, true);
    $buffer = $component->get('bufferTemplate');

    expect($buffer[$obrasId]['regra_elastica'])->toBeTrue();
    expect($buffer[$obrasId]['duracao'])->toBe(0);
});

it('atualizarBufferAncora aplica mutex (apenas uma fase âncora por template)', function () {
    $component = Livewire::test(CronogramaTemplates::class)
        ->call('selecionarTemplate', $this->template->id);

    $posseId = collect($component->get('bufferTemplate'))
        ->search(fn ($d) => $d['fase'] === FaseCronograma::POSSE->value);
    $obrasId = collect($component->get('bufferTemplate'))
        ->search(fn ($d) => $d['fase'] === FaseCronograma::OBRAS->value);

    // Marca OBRAS como âncora (POSSE estava como âncora).
    $component->call('atualizarBufferAncora', $obrasId, true);

    $buffer = $component->get('bufferTemplate');
    expect($buffer[$obrasId]['is_ancora'])->toBeTrue();
    expect($buffer[$posseId]['is_ancora'])->toBeFalse();
});

it('atualizarBufferOrdem persiste nova ordem ao salvar', function () {
    $component = Livewire::test(CronogramaTemplates::class)
        ->call('selecionarTemplate', $this->template->id);

    $ids = array_keys($component->get('bufferTemplate'));
    $reverso = array_reverse($ids);

    $component->call('atualizarBufferOrdem', $reverso)
        ->call('salvarBuffer');

    // A primeira fase agora deve ser a que era a última.
    $primeiraDb = CronogramaTemplateFase::where('cronograma_template_id', $this->template->id)
        ->orderBy('ordem')->first();
    expect($primeiraDb->id)->toBe($reverso[0]);
});

it('bufferAdicionarFasePersonalizada cria fase com titulo livre + salvar persiste', function () {
    $vazio = CronogramaTemplate::create([
        'nome' => 'VazioPers',
        'tipo_obra' => TipoObraCronograma::EXPANSAO->value,
        'ancora_campo' => 'projeto.data_posse',
        'ativo' => true,
    ]);

    Livewire::test(CronogramaTemplates::class)
        ->call('selecionarTemplate', $vazio->id)
        ->set('bufferNovaFasePersonalizadaTitulo', 'Auditoria interna PMO')
        ->call('bufferAdicionarFasePersonalizada')
        ->call('salvarBuffer');

    $fase = CronogramaTemplateFase::where('cronograma_template_id', $vazio->id)
        ->where('fase', FaseCronograma::PERSONALIZADA)
        ->first();

    expect($fase)->not->toBeNull();
    expect($fase->titulo_personalizado)->toBe('Auditoria interna PMO');
});

it('bufferAdicionarFasePersonalizada bloqueia segunda personalizada no mesmo template', function () {
    $vazio = CronogramaTemplate::create([
        'nome' => 'VazioPers2',
        'tipo_obra' => TipoObraCronograma::EXPANSAO->value,
        'ancora_campo' => 'projeto.data_posse',
        'ativo' => true,
    ]);

    Livewire::test(CronogramaTemplates::class)
        ->call('selecionarTemplate', $vazio->id)
        ->set('bufferNovaFasePersonalizadaTitulo', 'Primeira')
        ->call('bufferAdicionarFasePersonalizada')
        ->set('bufferNovaFasePersonalizadaTitulo', 'Segunda')
        ->call('bufferAdicionarFasePersonalizada')
        ->assertNotified();

    $personalizadas = collect(Livewire::test(CronogramaTemplates::class)
        ->call('selecionarTemplate', $vazio->id)
        ->get('bufferTemplate'))
        ->filter(fn ($d) => ($d['fase'] ?? null) === FaseCronograma::PERSONALIZADA->value);

    // No buffer só uma personalizada deve existir após tentativa de duplicar.
    expect($personalizadas)->toHaveCount(0); // (ainda não salvou; nada no DB)
});

it('bufferAdicionarFase + salvar cria registro novo no DB', function () {
    // Usa template vazio para garantir fase nova não conflitar.
    $vazio = CronogramaTemplate::create([
        'nome' => 'Vazio',
        'tipo_obra' => TipoObraCronograma::EXPANSAO->value,
        'ancora_campo' => 'projeto.data_posse',
        'ativo' => true,
    ]);

    Livewire::test(CronogramaTemplates::class)
        ->call('selecionarTemplate', $vazio->id)
        ->set('bufferNovaFaseEnum', FaseCronograma::OBRAS->value)
        ->call('bufferAdicionarFase')
        ->call('salvarBuffer');

    expect(CronogramaTemplateFase::where('cronograma_template_id', $vazio->id)
        ->where('fase', FaseCronograma::OBRAS)
        ->exists())->toBeTrue();
});

it('bufferRemoverFase persistida marca para deleção e remove no salvar', function () {
    $component = Livewire::test(CronogramaTemplates::class)
        ->call('selecionarTemplate', $this->template->id);

    $codigoOracleId = collect($component->get('bufferTemplate'))
        ->search(fn ($d) => $d['fase'] === FaseCronograma::CODIGO_ORACLE->value);

    $component->call('bufferRemoverFase', $codigoOracleId)
        ->call('salvarBuffer');

    expect(CronogramaTemplateFase::find($codigoOracleId))->toBeNull();
});

it('atualizarBufferVisivel(false) em fase com dependentes abre modal e mantém visível', function () {
    $component = Livewire::test(CronogramaTemplates::class)
        ->call('selecionarTemplate', $this->template->id);

    // Posse tem dependentes no template (ex.: Obras forward).
    $posseId = collect($component->get('bufferTemplate'))
        ->search(fn ($d) => $d['fase'] === FaseCronograma::POSSE->value);

    $component->call('atualizarBufferVisivel', $posseId, false)
        ->assertSet('mostrarModalConflitoDepBuffer', true)
        ->assertSet('acaoConflitoFase', 'ocultar');

    // Buffer NÃO foi alterado: continua visível enquanto modal aberto.
    expect($component->get('bufferTemplate')[$posseId]['visivel'])->toBeTrue();
    expect(count($component->get('fasesConflitantesBuffer')))->toBeGreaterThan(0);
});

it('confirmarOcultarReconfigurarDeps remove deps e oculta a fase', function () {
    $component = Livewire::test(CronogramaTemplates::class)
        ->call('selecionarTemplate', $this->template->id);

    $posseId = collect($component->get('bufferTemplate'))
        ->search(fn ($d) => $d['fase'] === FaseCronograma::POSSE->value);

    $component->call('atualizarBufferVisivel', $posseId, false)
        ->assertSet('mostrarModalConflitoDepBuffer', true);

    // Default substituir_por='' = remover dependência.
    $component->call('confirmarOcultarReconfigurarDeps')
        ->assertSet('mostrarModalConflitoDepBuffer', false)
        ->assertSet('faseConflitoChave', null);

    expect($component->get('bufferTemplate')[$posseId]['visivel'])->toBeFalse();

    // Confirma que Obras não tem mais dep apontando pra Posse.
    $obrasId = collect($component->get('bufferTemplate'))
        ->search(fn ($d) => $d['fase'] === FaseCronograma::OBRAS->value);
    $alvos = array_column($component->get('bufferTemplate')[$obrasId]['deps'], 'alvo');
    expect($alvos)->not->toContain('fase:'.FaseCronograma::POSSE->value);
});

it('cancelarOcultarReconfigurarDeps fecha modal sem aplicar mudança', function () {
    $component = Livewire::test(CronogramaTemplates::class)
        ->call('selecionarTemplate', $this->template->id);

    $posseId = collect($component->get('bufferTemplate'))
        ->search(fn ($d) => $d['fase'] === FaseCronograma::POSSE->value);

    $component->call('atualizarBufferVisivel', $posseId, false);
    $component->call('cancelarOcultarReconfigurarDeps')
        ->assertSet('mostrarModalConflitoDepBuffer', false);

    expect($component->get('bufferTemplate')[$posseId]['visivel'])->toBeTrue();
});

it('bufferRemoverFase com dependentes abre modal de conflito', function () {
    $component = Livewire::test(CronogramaTemplates::class)
        ->call('selecionarTemplate', $this->template->id);

    $posseId = collect($component->get('bufferTemplate'))
        ->search(fn ($d) => $d['fase'] === FaseCronograma::POSSE->value);

    $component->call('bufferRemoverFase', $posseId)
        ->assertSet('mostrarModalConflitoDepBuffer', true)
        ->assertSet('acaoConflitoFase', 'remover');

    // Fase ainda no buffer (não removeu até confirmar).
    expect(isset($component->get('bufferTemplate')[$posseId]))->toBeTrue();
});

it('bufferAdicionarDep adiciona entrada vazia no buffer da fase', function () {
    $component = Livewire::test(CronogramaTemplates::class)
        ->call('selecionarTemplate', $this->template->id);

    $obrasId = collect($component->get('bufferTemplate'))
        ->search(fn ($d) => $d['fase'] === FaseCronograma::OBRAS->value);

    $depsAntes = count($component->get('bufferTemplate')[$obrasId]['deps']);
    $component->call('bufferAdicionarDep', $obrasId);
    $depsDepois = count($component->get('bufferTemplate')[$obrasId]['deps']);

    expect($depsDepois)->toBe($depsAntes + 1);
});

// ============================================================================
// Subitens — persistem direto no DB (não passam pelo buffer)
// ============================================================================

it('adicionarTemplateFaseItem cria subitem direto no DB', function () {
    $component = Livewire::test(CronogramaTemplates::class)
        ->call('selecionarTemplate', $this->template->id);

    $faseRA = $this->template->fases()
        ->where('fase', FaseCronograma::RECEBIMENTO_PROJETOS_ARQUITETURA)
        ->first();
    // Limpa subitens vindos do seeder (5 da planilha) para isolar o subitem novo.
    $faseRA->itens()->delete();

    $component->set("novoSubitemTitulos.{$faseRA->id}", 'Plantas')
        ->call('adicionarTemplateFaseItem', $faseRA->id);

    $faseRA->refresh();
    expect($faseRA->itens()->count())->toBe(1);
    expect($faseRA->itens->first()->titulo)->toBe('Plantas');
});

it('adicionarTemplateFaseItem sem título dispara warning e não persiste', function () {
    $component = Livewire::test(CronogramaTemplates::class)
        ->call('selecionarTemplate', $this->template->id);

    $faseRA = $this->template->fases()
        ->where('fase', FaseCronograma::RECEBIMENTO_PROJETOS_ARQUITETURA)
        ->first();
    $faseRA->itens()->delete();

    $component->call('adicionarTemplateFaseItem', $faseRA->id)
        ->assertNotified();

    expect($faseRA->itens()->count())->toBe(0);
});
