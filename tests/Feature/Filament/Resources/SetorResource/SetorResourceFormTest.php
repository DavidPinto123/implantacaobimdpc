<?php

use App\Filament\Resources\SetorResource\Pages\CreateSetor;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

beforeEach(function () {
    setupFilamentResourceCoverageForTests($this);
});

it('valida required no CreateSetor', function () {
    Livewire::test(CreateSetor::class)
        ->assertFormFieldVisible('setor')
        ->fillForm(['setor' => null])
        ->call('create')
        ->assertHasFormErrors(['setor' => 'required']);
});
