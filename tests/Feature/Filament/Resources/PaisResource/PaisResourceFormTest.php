<?php

use App\Filament\Resources\PaisResource\Pages\CreatePais;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

beforeEach(function () {
    setupFilamentResourceCoverageForTests($this);
});

it('valida required no CreatePais', function () {
    Livewire::test(CreatePais::class)
        ->assertFormFieldVisible('nome')
        ->fillForm(['nome' => null])
        ->call('create')
        ->assertHasFormErrors(['nome' => 'required']);
});
