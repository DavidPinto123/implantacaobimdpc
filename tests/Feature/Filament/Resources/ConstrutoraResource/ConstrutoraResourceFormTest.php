<?php

use App\Filament\Resources\ConstrutoraResource\Pages\CreateConstrutora;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

beforeEach(function () {
    setupFilamentResourceCoverageForTests($this);
});

it('valida campos principais de fornecedores', function () {
    Livewire::test(CreateConstrutora::class)
        ->assertFormFieldVisible('nome')
        ->assertFormFieldVisible('cnpj')
        ->assertFormFieldVisible('tipo')
        ->assertFormFieldVisible('email')
        ->assertFormFieldVisible('inscricao_estadual')
        ->assertFormFieldVisible('endereco')
        ->assertFormFieldVisible('cep')
        ->assertFormFieldVisible('responsavel')
        ->fillForm([
            'nome' => '',
            'cnpj' => '',
            'tipo' => null,
            'email' => 'email-invalido',
        ])
        ->call('create')
        ->assertHasFormErrors([
            'nome' => 'required',
            'cnpj' => 'required',
            'tipo' => 'required',
            'email' => 'email',
        ]);
});
