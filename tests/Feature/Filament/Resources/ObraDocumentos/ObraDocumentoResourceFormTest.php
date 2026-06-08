<?php

use App\Filament\Resources\ObraDocumentos\Pages\EditObraDocumento;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

beforeEach(function () {
    setupFilamentResourceCoverageForTests($this);
});

it('cobre campos visíveis e required no EditObraDocumento', function () {
    $admin = auth()->user();
    $obra = createObraRecord($admin);
    $documento = createObraDocumentoRecord($obra, $admin);

    Livewire::test(EditObraDocumento::class, ['record' => $documento->getKey()])
        ->assertFormFieldVisible('obra_id')
        ->assertFormFieldVisible('nome')
        ->assertFormFieldVisible('status')
        ->assertFormFieldExists('obra_id', fn ($field): bool => (bool) $field->isRequired())
        ->assertFormFieldExists('nome', fn ($field): bool => (bool) $field->isRequired())
        ->assertFormFieldExists('status', fn ($field): bool => (bool) $field->isRequired());
});
