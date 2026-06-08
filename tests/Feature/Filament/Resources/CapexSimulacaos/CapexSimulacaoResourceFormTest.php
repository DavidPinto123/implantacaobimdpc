<?php

use App\Filament\Resources\CapexSimulacaos\Pages\CreateCapexSimulacao;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

beforeEach(function () {
    setupFilamentResourceCoverageForTests($this);
});

it('valida campos principais do CreateCapexSimulacao', function () {
    $createPage = Livewire::test(CreateCapexSimulacao::class);

    if (method_exists($createPage, 'assertFormFieldHidden')) {
        $createPage->assertFormFieldHidden('projeto_id');
    }

    $createPage
        ->assertFormFieldVisible('nome')
        ->assertFormFieldVisible('status')
        ->assertFormFieldVisible('area_unidade')
        ->fillForm([
            'vinculado' => true,
        ])
        ->assertFormFieldVisible('projeto_id')
        ->fillForm([
            'nome' => '',
            'status' => null,
            'area_unidade' => '',
        ])
        ->call('create')
        ->assertHasFormErrors([
            'nome' => 'required',
            'status' => 'required',
            'area_unidade' => 'required',
        ]);

    Livewire::test(CreateCapexSimulacao::class)
        ->fillForm([
            'nome' => 'Capex inválido cobertura',
            'status' => 0,
            'area_unidade' => -1,
        ])
        ->call('create')
        ->assertHasFormErrors(['area_unidade' => 'min']);
});
