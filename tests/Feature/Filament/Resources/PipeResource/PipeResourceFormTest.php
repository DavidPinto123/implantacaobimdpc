<?php

use App\Filament\Resources\PipeResource\Pages\CreatePipe;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

beforeEach(function () {
    setupFilamentResourceCoverageForTests($this);
});

it('valida required no CreatePipe', function () {
    Livewire::test(CreatePipe::class)
        ->assertFormFieldVisible('pipeline')
        ->fillForm(['pipeline' => null])
        ->call('create')
        ->assertHasFormErrors(['pipeline' => 'required']);
});
