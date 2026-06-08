<?php

use App\Filament\Resources\ControlePedidos\Pages\CreateControlePedido;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

beforeEach(function () {
    setupFilamentResourceCoverageForTests($this);
});

it('valida campos principais de ControlePedido', function () {
    Livewire::test(CreateControlePedido::class)
        ->assertFormFieldVisible('projeto_id')
        ->assertFormFieldVisible('status')
        ->assertFormFieldVisible('situacao')
        ->assertFormFieldVisible('valor_oi')
        ->fillForm([
            'projeto_id' => null,
            'status' => null,
            'situacao' => null,
            'valor_oi' => 'abc',
        ])
        ->call('create')
        ->assertHasFormErrors([
            'projeto_id' => 'required',
            'status' => 'required',
            'situacao' => 'required',
            'valor_oi' => 'numeric',
        ]);
});
