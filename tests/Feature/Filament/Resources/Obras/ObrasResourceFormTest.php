<?php

use App\Filament\Resources\Obras\Pages\CreateObras;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

beforeEach(function () {
    setupFilamentResourceCoverageForTests($this);
});

it('cobre campo projeto_id visível e obrigatório no CreateObras', function () {
    createProjetoRecord(auth()->user());

    Livewire::test(CreateObras::class)
        ->assertFormFieldVisible('projeto_id')
        ->fillForm(['projeto_id' => null])
        ->call('create')
        ->assertHasFormErrors(['projeto_id' => 'required']);
});
