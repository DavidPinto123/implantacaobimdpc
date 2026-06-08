<?php

use App\Filament\Resources\EmpresasResource\Pages\CreateEmpresas;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

beforeEach(function () {
    setupFilamentResourceCoverageForTests($this);
});

it('valida campos principais de empresas', function () {
    Livewire::test(CreateEmpresas::class)
        ->assertFormFieldVisible('nome')
        ->assertFormFieldVisible('nome_fantasia')
        ->assertFormFieldVisible('cnpj')
        ->assertFormFieldVisible('tipo')
        ->assertFormFieldVisible('status')
        ->assertFormFieldVisible('pais_id')
        ->assertFormFieldVisible('estado_id')
        ->assertFormFieldVisible('cidade_id')
        ->fillForm([
            'nome' => '',
            'nome_fantasia' => '',
            'cnpj' => '',
        ])
        ->call('create')
        ->assertHasFormErrors([
            'nome' => 'required',
            'nome_fantasia' => 'required',
            'cnpj' => 'required',
        ]);
});
