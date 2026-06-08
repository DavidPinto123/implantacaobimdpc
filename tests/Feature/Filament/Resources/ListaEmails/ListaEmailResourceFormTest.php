<?php

use App\Filament\Resources\ListaEmails\Pages\CreateListaEmail;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

beforeEach(function () {
    setupFilamentResourceCoverageForTests($this);
});

it('valida campos principais de lista de emails', function () {
    Livewire::test(CreateListaEmail::class)
        ->assertFormFieldVisible('nome')
        ->assertFormFieldVisible('descricao')
        ->assertFormFieldVisible('ativo')
        ->assertFormFieldVisible('emails')
        ->fillForm([
            'nome' => '',
            'emails' => [],
        ])
        ->call('create')
        ->assertHasFormErrors([
            'nome' => 'required',
            'emails' => 'required',
        ]);
});
