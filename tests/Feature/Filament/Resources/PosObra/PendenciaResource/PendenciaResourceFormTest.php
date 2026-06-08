<?php

use App\Enums\PosObra\StatusPendencia;
use App\Enums\PosObra\UrgenciaPendencia;
use App\Filament\Resources\PosObra\PendenciaResource\Pages\CreatePendencia;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

beforeEach(function () {
    setupFilamentResourceCoverageForTests($this);
});

it('valida required essenciais no CreatePendencia', function () {
    $admin = auth()->user();
    $obra = createObraRecord($admin);

    Livewire::test(CreatePendencia::class)
        ->assertFormFieldVisible('obras_id')
        ->assertFormFieldVisible('urgencia')
        ->assertFormFieldVisible('gestor_id')
        ->assertFormFieldVisible('descricao')
        ->assertFormFieldVisible('status')
        ->fillForm([
            'obras_id' => null,
            'urgencia' => null,
            'gestor_id' => null,
            'descricao' => '',
            'status' => null,
        ])
        ->call('create')
        ->assertHasFormErrors([
            'obras_id' => 'required',
            'urgencia' => 'required',
            'gestor_id' => 'required',
            'descricao' => 'required',
            'status' => 'required',
        ]);

    Livewire::test(CreatePendencia::class)
        ->fillForm([
            'obras_id' => $obra->id,
            'urgencia' => UrgenciaPendencia::P2->value,
            'gestor_id' => $admin->id,
            'descricao' => 'Pendência de cobertura',
            'status' => StatusPendencia::REGISTRADA->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();
});
