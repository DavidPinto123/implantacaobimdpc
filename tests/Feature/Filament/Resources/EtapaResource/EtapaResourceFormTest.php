<?php

use App\Filament\Resources\EtapaResource\Pages\CreateEtapa;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

beforeEach(function () {
    setupFilamentResourceCoverageForTests($this);
});

it('valida required no CreateEtapa', function () {
    Livewire::test(CreateEtapa::class)
        ->assertFormFieldVisible('nome')
        ->fillForm(['nome' => null])
        ->call('create')
        ->assertHasFormErrors(['nome' => 'required']);
});
