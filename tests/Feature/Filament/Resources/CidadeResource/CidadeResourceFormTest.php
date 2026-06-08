<?php

use App\Filament\Resources\CidadeResource\Pages\CreateCidade;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

beforeEach(function () {
    setupFilamentResourceCoverageForTests($this);
});

it('valida required no CreateCidade', function () {
    Livewire::test(CreateCidade::class)
        ->assertFormFieldVisible('estado_id')
        ->assertFormFieldVisible('nome')
        ->fillForm([
            'estado_id' => null,
            'nome' => null,
        ])
        ->call('create')
        ->assertHasFormErrors([
            'estado_id' => 'required',
            'nome' => 'required',
        ]);
});
