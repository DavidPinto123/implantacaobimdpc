<?php

use App\Filament\Resources\MatterportResource\Pages\CreateMatterport;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

beforeEach(function () {
    setupFilamentResourceCoverageForTests($this);
});

it('valida campos principais de Matterport', function () {
    Livewire::test(CreateMatterport::class)
        ->assertFormFieldVisible('codigo')
        ->assertFormFieldVisible('pais_id')
        ->assertFormFieldVisible('estado_id')
        ->assertFormFieldVisible('cidade_id')
        ->assertFormFieldVisible('endereco')
        ->fillForm([
            'codigo' => '',
            'pais_id' => null,
            'estado_id' => null,
            'cidade_id' => null,
            'endereco' => '',
        ])
        ->call('create')
        ->assertHasFormErrors([
            'codigo' => 'required',
            'pais_id' => 'required',
            'estado_id' => 'required',
            'cidade_id' => 'required',
            'endereco' => 'required',
        ]);
});
