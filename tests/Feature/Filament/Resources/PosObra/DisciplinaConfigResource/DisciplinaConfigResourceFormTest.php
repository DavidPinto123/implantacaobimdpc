<?php

use App\Filament\Resources\PosObra\DisciplinaConfigResource\Pages\CreateDisciplinaConfig;
use App\Models\PosObra\DisciplinaConfig;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

beforeEach(function () {
    setupFilamentResourceCoverageForTests($this);
});

it('valida required unique e max no CreateDisciplinaConfig', function () {
    DisciplinaConfig::query()->create([
        'codigo' => 'DISC-COVER',
        'label' => 'Disciplina Cobertura',
        'ordem' => 1,
        'ativo' => true,
    ]);

    Livewire::test(CreateDisciplinaConfig::class)
        ->assertFormFieldVisible('codigo')
        ->assertFormFieldVisible('label')
        ->assertFormFieldVisible('ordem')
        ->assertFormFieldVisible('ativo')
        ->fillForm([
            'codigo' => '',
            'label' => '',
        ])
        ->call('create')
        ->assertHasFormErrors([
            'codigo' => 'required',
            'label' => 'required',
        ]);

    Livewire::test(CreateDisciplinaConfig::class)
        ->fillForm([
            'codigo' => 'DISC-COVER',
            'label' => str_repeat('A', 101),
            'ordem' => 1,
            'ativo' => true,
        ])
        ->call('create')
        ->assertHasFormErrors([
            'codigo' => 'unique',
            'label' => 'max',
        ]);
});
