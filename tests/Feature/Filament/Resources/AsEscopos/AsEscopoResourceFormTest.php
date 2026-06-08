<?php

use App\Filament\Resources\AsEscopos\Pages\CreateAsEscopo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

beforeEach(function () {
    setupFilamentResourceCoverageForTests($this);
});

it('valida campos e unicidade no CreateAsEscopo', function () {
    createAsEscopoRecord(['numero_as' => 'AS-UNICO-001', 'escopo' => 'Escopo Único Cobertura']);

    Livewire::test(CreateAsEscopo::class)
        ->assertFormFieldVisible('grupo')
        ->assertFormFieldVisible('numero_as')
        ->assertFormFieldVisible('escopo')
        ->assertFormFieldVisible('is_active')
        ->fillForm([
            'grupo' => null,
            'numero_as' => '',
            'escopo' => '',
        ])
        ->call('create')
        ->assertHasFormErrors([
            'grupo' => 'required',
            'numero_as' => 'required',
            'escopo' => 'required',
        ]);

    Livewire::test(CreateAsEscopo::class)
        ->fillForm([
            'grupo' => 'Civil',
            'numero_as' => 'AS-UNICO-001',
            'escopo' => 'Escopo Único Cobertura',
        ])
        ->call('create')
        ->assertHasFormErrors([
            'numero_as' => 'unique',
            'escopo' => 'unique',
        ]);
});

it('calcula automaticamente o outro percentual padrao para totalizar 100 no CreateAsEscopo', function () {
    Livewire::test(CreateAsEscopo::class)
        ->set('data.percentual_faturamento_mao_obra_default', 73.25)
        ->assertSet('data.percentual_faturamento_mao_obra_default', '73,25')
        ->assertSet('data.percentual_faturamento_material_default', '26,75')
        ->set('data.percentual_faturamento_mao_obra_default', 12.34)
        ->assertSet('data.percentual_faturamento_mao_obra_default', '12,34')
        ->assertSet('data.percentual_faturamento_material_default', '87,66')
        ->set('data.percentual_faturamento_material_default', 44.4)
        ->assertSet('data.percentual_faturamento_material_default', '44,40')
        ->assertSet('data.percentual_faturamento_mao_obra_default', '55,60');
});

it('normaliza o proprio percentual padrao para duas casas e maximo 100 no blur', function () {
    Livewire::test(CreateAsEscopo::class)
        ->set('data.percentual_faturamento_mao_obra_default', '100,01')
        ->assertSet('data.percentual_faturamento_mao_obra_default', '100,00')
        ->assertSet('data.percentual_faturamento_material_default', '0,00')
        ->set('data.percentual_faturamento_material_default', '33.333')
        ->assertSet('data.percentual_faturamento_material_default', '33,33')
        ->assertSet('data.percentual_faturamento_mao_obra_default', '66,67');
});

it('aceita ponto e salva percentuais padrao com duas casas decimais no CreateAsEscopo', function () {
    Livewire::test(CreateAsEscopo::class)
        ->fillForm([
            'grupo' => 'Civil',
            'numero_as' => 'AS-PERCENTUAL-PONTO',
            'escopo' => 'Escopo Percentual Ponto',
            'percentual_faturamento_mao_obra_default' => '73.259',
            'percentual_faturamento_material_default' => '26.741',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('as_escopos', [
        'numero_as' => 'AS-PERCENTUAL-PONTO',
        'percentual_faturamento_mao_obra_default' => 73.26,
        'percentual_faturamento_material_default' => 26.74,
    ]);
});

it('aceita virgula e salva percentuais padrao com duas casas decimais no CreateAsEscopo', function () {
    Livewire::test(CreateAsEscopo::class)
        ->fillForm([
            'grupo' => 'Civil',
            'numero_as' => 'AS-PERC-VIRG',
            'escopo' => 'Escopo Percentual Virgula',
            'percentual_faturamento_mao_obra_default' => '73,259',
            'percentual_faturamento_material_default' => '26,741',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('as_escopos', [
        'numero_as' => 'AS-PERC-VIRG',
        'percentual_faturamento_mao_obra_default' => 73.26,
        'percentual_faturamento_material_default' => 26.74,
    ]);
});

it('limita percentual padrao maior que 100 no CreateAsEscopo', function () {
    Livewire::test(CreateAsEscopo::class)
        ->fillForm([
            'grupo' => 'Civil',
            'numero_as' => 'AS-PERC-MAIOR',
            'escopo' => 'Escopo Percentual Maior',
            'percentual_faturamento_mao_obra_default' => '100,01',
            'percentual_faturamento_material_default' => '0',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('as_escopos', [
        'numero_as' => 'AS-PERC-MAIOR',
        'percentual_faturamento_mao_obra_default' => 100,
        'percentual_faturamento_material_default' => 0,
    ]);
});
