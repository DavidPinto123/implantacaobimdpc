<?php

use App\Enums\ModoAncoraCronograma;
use App\Enums\TipoObraCronograma;
use App\Filament\Pages\Cronograma;
use App\Models\CronogramaTemplate;
use App\Models\CronogramaTemplateFase;
use App\Models\Projeto;
use Database\Factories\ProjetoFactory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

beforeEach(function () {
    setupAdminPanelForTests();
    $this->user = createActiveUserWithPermissions(['View:Cronograma']);
    $this->actingAs($this->user);

    // Pareia o template Smart Fit (POSSE) com uma cópia em modo OBRAS.
    $this->posse = seedTemplateSmartFit();
    $this->posse->update(['modo_ancora' => 'posse']);

    $this->obras = $this->posse->replicate(['template_pareado_id']);
    $this->obras->nome = $this->posse->nome . ' — OBRAS';
    $this->obras->modo_ancora = 'obras';
    $this->obras->ancora_campo = 'projeto.data_inicio_obra';
    $this->obras->save();

    foreach ($this->posse->fases as $fase) {
        $faseNova = $fase->replicate();
        $faseNova->cronograma_template_id = $this->obras->id;
        $faseNova->save();
    }

    $this->posse->update(['template_pareado_id' => $this->obras->id]);
    $this->obras->update(['template_pareado_id' => $this->posse->id]);
});

it('definirModoAncora ativa modal de reaplicar quando o template tem par', function () {
    $projeto = aplicarSmartFit('2026-08-01');
    $projeto->update(['modo_ancora' => 'posse']);

    Livewire::test(Cronograma::class)
        ->set('projetoSelecionado', $projeto->id)
        ->call('definirModoAncora', 'obras')
        ->assertSet('mostrarConfirmacaoReaplicar', true)
        ->assertSet('varianteSugeridaId', $this->obras->id);

    expect($projeto->fresh()->modo_ancora)->toBe(ModoAncoraCronograma::OBRAS);
});

it('definirModoAncora NÃO ativa modal quando template não tem par', function () {
    // Desfaz o pareamento criado no beforeEach
    $this->posse->update(['template_pareado_id' => null]);
    $this->obras->update(['template_pareado_id' => null]);

    $projeto = aplicarSmartFit('2026-08-01');
    $projeto->update(['modo_ancora' => 'posse']);

    Livewire::test(Cronograma::class)
        ->set('projetoSelecionado', $projeto->id)
        ->call('definirModoAncora', 'obras')
        ->assertSet('mostrarConfirmacaoReaplicar', false);
});

it('cancelarReaplicarVariante limpa estado do modal', function () {
    $projeto = aplicarSmartFit('2026-08-01');
    $projeto->update(['modo_ancora' => 'posse']);

    Livewire::test(Cronograma::class)
        ->set('projetoSelecionado', $projeto->id)
        ->call('definirModoAncora', 'obras')
        ->call('cancelarReaplicarVariante')
        ->assertSet('mostrarConfirmacaoReaplicar', false)
        ->assertSet('varianteSugeridaId', null);
});

it('confirmarReaplicarVariante reaplica o template e fecha o modal', function () {
    $projeto = aplicarSmartFit('2026-08-01');
    $projeto->update(['modo_ancora' => 'posse']);

    Livewire::test(Cronograma::class)
        ->set('projetoSelecionado', $projeto->id)
        ->call('definirModoAncora', 'obras')
        ->call('confirmarReaplicarVariante')
        ->assertSet('mostrarConfirmacaoReaplicar', false)
        ->assertSet('varianteSugeridaId', null);

    // Após reaplicar, as fases do projeto devem estar amarradas à variante OBRAS.
    $fasesProjeto = $projeto->fresh()->cronogramaFases()
        ->whereNotNull('cronograma_template_id')
        ->pluck('cronograma_template_id')
        ->unique();

    expect($fasesProjeto)->toContain($this->obras->id);
});
