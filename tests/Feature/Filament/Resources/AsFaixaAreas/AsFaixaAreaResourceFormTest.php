<?php

use App\Filament\Resources\AsFaixaAreas\Pages\CreateAsFaixaArea;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

beforeEach(function () {
    setupFilamentResourceCoverageForTests($this);
});

it('valida required, numeric, min e gt no CreateAsFaixaArea', function () {
    Livewire::test(CreateAsFaixaArea::class)
        ->assertFormFieldVisible('nome')
        ->assertFormFieldVisible('area_min')
        ->assertFormFieldVisible('area_max')
        ->fillForm([
            'nome' => '',
            'area_min' => '',
            'area_max' => 'x',
        ])
        ->call('create')
        ->assertHasFormErrors([
            'nome' => 'required',
            'area_min' => 'required',
            'area_max' => 'numeric',
        ]);

    Livewire::test(CreateAsFaixaArea::class)
        ->fillForm([
            'nome' => 'Faixa Cobertura',
            'area_min' => -1,
            'area_max' => 0,
        ])
        ->call('create')
        ->assertHasFormErrors(['area_min' => 'min']);

    Livewire::test(CreateAsFaixaArea::class)
        ->fillForm([
            'nome' => 'Faixa Cobertura 2',
            'area_min' => 10,
            'area_max' => 10,
        ])
        ->call('create')
        ->assertHasFormErrors(['area_max' => 'gt']);
});
