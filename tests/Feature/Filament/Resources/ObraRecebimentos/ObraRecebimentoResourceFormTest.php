<?php

use App\Filament\Resources\ObraRecebimentos\Pages\EditObraRecebimento;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

beforeEach(function () {
    setupFilamentResourceCoverageForTests($this);
});

it('cobre campos visíveis e required no EditObraRecebimento', function () {
    $admin = auth()->user();
    $obra = createObraRecord($admin);
    $recebimento = createObraRecebimentoRecord($obra, null, $admin);

    Livewire::test(EditObraRecebimento::class, ['record' => $recebimento->getKey()])
        ->assertFormFieldVisible('obra_id')
        ->assertFormFieldVisible('nome')
        ->assertFormFieldVisible('status')
        ->assertFormFieldExists('obra_id', fn ($field): bool => (bool) $field->isRequired())
        ->assertFormFieldExists('nome', fn ($field): bool => (bool) $field->isRequired())
        ->assertFormFieldExists('status', fn ($field): bool => (bool) $field->isRequired());
});
