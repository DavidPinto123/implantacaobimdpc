<?php

use App\Filament\Resources\MarcaResource\Pages\CreateMarca;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

beforeEach(function () {
    setupFilamentResourceCoverageForTests($this);
});

it('valida required no CreateMarca', function () {
    Livewire::test(CreateMarca::class)
        ->assertFormFieldVisible('nome')
        ->fillForm(['nome' => null])
        ->call('create')
        ->assertHasFormErrors(['nome' => 'required']);
});
