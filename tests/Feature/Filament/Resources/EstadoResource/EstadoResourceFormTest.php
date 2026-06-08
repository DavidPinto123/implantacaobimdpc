<?php

use App\Filament\Resources\EstadoResource\Pages\CreateEstado;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

beforeEach(function () {
    setupFilamentResourceCoverageForTests($this);
});

it('valida required no CreateEstado', function () {
    Livewire::test(CreateEstado::class)
        ->assertFormFieldVisible('pais_id')
        ->assertFormFieldVisible('nome')
        ->fillForm([
            'pais_id' => null,
            'nome' => null,
        ])
        ->call('create')
        ->assertHasFormErrors([
            'pais_id' => 'required',
            'nome' => 'required',
        ]);
});
