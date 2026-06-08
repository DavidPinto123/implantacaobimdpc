<?php

use App\Filament\Resources\TaskCategories\Pages\CreateTaskCategory;
use App\Models\TaskCategory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

beforeEach(function () {
    setupFilamentResourceCoverageForTests($this);
});

it('valida campo obrigatório e único no CreateTaskCategory', function () {
    TaskCategory::query()->create(['name' => 'Categoria Única Cobertura']);

    Livewire::test(CreateTaskCategory::class)
        ->assertFormFieldVisible('name')
        ->fillForm(['name' => ''])
        ->call('create')
        ->assertHasFormErrors(['name' => 'required']);

    Livewire::test(CreateTaskCategory::class)
        ->fillForm(['name' => 'Categoria Única Cobertura'])
        ->call('create')
        ->assertHasFormErrors(['name' => 'unique']);
});
