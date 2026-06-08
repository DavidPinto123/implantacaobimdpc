<?php

use App\Enums\PosObra\UrgenciaPendencia;
use App\Filament\Resources\PosObra\ConfiguracaoSlaResource\Pages\CreateConfiguracaoSla;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

beforeEach(function () {
    setupFilamentResourceCoverageForTests($this);
});

it('valida campos e min numeric no CreateConfiguracaoSla', function () {
    Livewire::test(CreateConfiguracaoSla::class)
        ->assertFormFieldVisible('urgencia')
        ->assertFormFieldVisible('prazo_horas')
        ->assertFormFieldVisible('ativo')
        ->fillForm([
            'urgencia' => null,
            'prazo_horas' => '',
        ])
        ->call('create')
        ->assertHasFormErrors([
            'urgencia' => 'required',
            'prazo_horas' => 'required',
        ]);

    Livewire::test(CreateConfiguracaoSla::class)
        ->fillForm([
            'urgencia' => UrgenciaPendencia::P1->value,
            'prazo_horas' => 0,
        ])
        ->call('create')
        ->assertHasFormErrors(['prazo_horas' => 'min']);

    Livewire::test(CreateConfiguracaoSla::class)
        ->fillForm([
            'urgencia' => UrgenciaPendencia::P2->value,
            'prazo_horas' => 'abc',
        ])
        ->call('create')
        ->assertHasFormErrors(['prazo_horas' => 'numeric']);
});
