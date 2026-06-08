<?php

use App\Enums\ModoAncoraCronograma;
use App\Enums\TipoObraCronograma;
use App\Filament\Pages\CronogramaTemplates;
use App\Models\CronogramaTemplate;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

beforeEach(function () {
    setupAdminPanelForTests();
    $this->user = createActiveUserWithPermissions(['View:CronogramaTemplates']);
    $this->actingAs($this->user);

    $this->template = seedTemplateSmartFit();
    $this->template->update(['modo_ancora' => 'posse']);
});

it('criarVariantePareada duplica fases/itens e amarra os dois templates', function () {
    $idOriginal = $this->template->id;
    $countFasesAntes = $this->template->fases()->count();
    $countItensAntes = \App\Models\CronogramaTemplateFaseItem::whereIn(
        'cronograma_template_fase_id', $this->template->fases()->pluck('id')
    )->count();

    Livewire::test(CronogramaTemplates::class)
        ->call('selecionarTemplate', $idOriginal)
        ->call('criarVariantePareada');

    $original = CronogramaTemplate::find($idOriginal);
    $par = CronogramaTemplate::find($original->template_pareado_id);

    expect($par)->not->toBeNull();
    expect($par->modo_ancora)->toBe(ModoAncoraCronograma::OBRAS);
    expect($par->ancora_campo)->toBe('projeto.data_inicio_obra');
    expect($par->template_pareado_id)->toBe($original->id);
    expect($par->fases()->count())->toBe($countFasesAntes);

    $itensPar = \App\Models\CronogramaTemplateFaseItem::whereIn(
        'cronograma_template_fase_id', $par->fases()->pluck('id')
    )->count();
    expect($itensPar)->toBe($countItensAntes);
});

it('criarVariantePareada não duplica se já tem par', function () {
    Livewire::test(CronogramaTemplates::class)
        ->call('selecionarTemplate', $this->template->id)
        ->call('criarVariantePareada');

    $countAposPrimeiro = CronogramaTemplate::count();

    // O selecionarTemplate dentro de criarVariantePareada redirecionou para o par.
    // Tenta criar de novo a partir do par — deveria falhar.
    Livewire::test(CronogramaTemplates::class)
        ->call('selecionarTemplate', $this->template->fresh()->template_pareado_id)
        ->call('criarVariantePareada');

    expect(CronogramaTemplate::count())->toBe($countAposPrimeiro);
});

it('irParaVariante alterna templateSelecionadoId para o par', function () {
    Livewire::test(CronogramaTemplates::class)
        ->call('selecionarTemplate', $this->template->id)
        ->call('criarVariantePareada');

    $parId = $this->template->fresh()->template_pareado_id;

    Livewire::test(CronogramaTemplates::class)
        ->call('selecionarTemplate', $this->template->id)
        ->call('irParaVariante', 'obras')
        ->assertSet('templateSelecionadoId', $parId);
});

it('parearComTemplate liga dois templates com modos opostos', function () {
    $outroPosse = CronogramaTemplate::create([
        'nome' => 'Outro POSSE',
        'tipo_obra' => TipoObraCronograma::EXPANSAO->value,
        'ancora_campo' => 'projeto.data_posse',
        'modo_ancora' => 'posse',
        'ativo' => true,
    ]);

    $candidatoObras = CronogramaTemplate::create([
        'nome' => 'Candidato OBRAS',
        'tipo_obra' => TipoObraCronograma::EXPANSAO->value,
        'ancora_campo' => 'projeto.data_inicio_obra',
        'modo_ancora' => 'obras',
        'ativo' => true,
    ]);

    Livewire::test(CronogramaTemplates::class)
        ->call('selecionarTemplate', $outroPosse->id)
        ->call('parearComTemplate', $candidatoObras->id);

    expect($outroPosse->fresh()->template_pareado_id)->toBe($candidatoObras->id);
    expect($candidatoObras->fresh()->template_pareado_id)->toBe($outroPosse->id);
});

it('parearComTemplate rejeita templates com mesmo modo', function () {
    $outroPosse = CronogramaTemplate::create([
        'nome' => 'Outro POSSE',
        'tipo_obra' => TipoObraCronograma::EXPANSAO->value,
        'ancora_campo' => 'projeto.data_posse',
        'modo_ancora' => 'posse',
        'ativo' => true,
    ]);

    Livewire::test(CronogramaTemplates::class)
        ->call('selecionarTemplate', $this->template->id)
        ->call('parearComTemplate', $outroPosse->id);

    expect($this->template->fresh()->template_pareado_id)->toBeNull();
    expect($outroPosse->fresh()->template_pareado_id)->toBeNull();
});

it('parearComTemplate rejeita pareamento consigo mesmo', function () {
    Livewire::test(CronogramaTemplates::class)
        ->call('selecionarTemplate', $this->template->id)
        ->call('parearComTemplate', $this->template->id);

    expect($this->template->fresh()->template_pareado_id)->toBeNull();
});

it('desfazerPareamento limpa os dois lados', function () {
    Livewire::test(CronogramaTemplates::class)
        ->call('selecionarTemplate', $this->template->id)
        ->call('criarVariantePareada');

    $parId = $this->template->fresh()->template_pareado_id;
    expect($parId)->not->toBeNull();

    Livewire::test(CronogramaTemplates::class)
        ->call('selecionarTemplate', $this->template->id)
        ->call('desfazerPareamento');

    expect($this->template->fresh()->template_pareado_id)->toBeNull();
    expect(CronogramaTemplate::find($parId)->template_pareado_id)->toBeNull();
});

it('mount restaura templateSelecionadoId vindo da URL', function () {
    // Simula que veio um ?tpl={id} via URL — Livewire sincroniza com a propriedade.
    Livewire::withQueryParams(['tpl' => $this->template->id])
        ->test(CronogramaTemplates::class)
        ->assertSet('templateSelecionadoId', $this->template->id)
        ->assertSet('tplNome', $this->template->nome)
        ->assertSet('mostrarEditorFases', true);
});
