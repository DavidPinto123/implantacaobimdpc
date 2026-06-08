<?php

use App\Filament\Pages\CadastrarPonto;
use App\Models\Etapa;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

beforeEach(function (): void {
    setupFilamentResourceCoverageForTests($this);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    ensureDefaultRoles();
});

it('marca como obrigatórios os campos exigidos para cadastrar ponto', function (): void {
    $user = createActiveUserWithPermissions(['View:CadastrarPonto']);
    $this->actingAs($user);

    Etapa::firstOrCreate(['nome' => 'Prospecção']);

    Livewire::test(CadastrarPonto::class)
        ->assertFormFieldExists('codigo', fn ($field): bool => (bool) $field->isRequired())
        ->assertFormFieldExists('data_posse', fn ($field): bool => (bool) $field->isRequired())
        ->assertFormFieldExists('nome', fn ($field): bool => (bool) $field->isRequired())
        ->assertFormFieldExists('pais_id', fn ($field): bool => (bool) $field->isRequired())
        ->assertFormFieldExists('estado_id', fn ($field): bool => (bool) $field->isRequired())
        ->assertFormFieldExists('cidade_id', fn ($field): bool => (bool) $field->isRequired())
        ->assertFormFieldExists('area_academia', fn ($field): bool => (bool) $field->isRequired());
});

it('permite buscar pais estado e cidade dentro dos selects', function (): void {
    $user = createActiveUserWithPermissions(['View:CadastrarPonto']);
    $this->actingAs($user);

    Etapa::firstOrCreate(['nome' => 'Prospecção']);

    Livewire::test(CadastrarPonto::class)
        ->assertFormFieldExists('pais_id', fn ($field): bool => $field->isSearchable() && $field->isPreloaded())
        ->assertFormFieldExists('estado_id', fn ($field): bool => $field->isSearchable() && $field->isPreloaded())
        ->assertFormFieldExists('cidade_id', fn ($field): bool => $field->isSearchable() && $field->isPreloaded());
});

it('bloqueia estado e cidade ate que o campo anterior seja preenchido', function (): void {
    $user = createActiveUserWithPermissions(['View:CadastrarPonto']);
    $this->actingAs($user);

    Etapa::firstOrCreate(['nome' => 'Prospecção']);

    Livewire::test(CadastrarPonto::class)
        ->assertFormFieldEnabled('pais_id')
        ->assertFormFieldDisabled('estado_id')
        ->assertFormFieldDisabled('cidade_id');
});

it('valida localização antes de persistir o ponto', function (): void {
    $user = createActiveUserWithPermissions(['View:CadastrarPonto']);
    $this->actingAs($user);

    Etapa::firstOrCreate(['nome' => 'Prospecção']);

    Livewire::test(CadastrarPonto::class)
        ->fillForm([
            'codigo' => 'ABCTest',
            'nome' => 'Unidade Teste',
            'data_posse' => now()->addDays(13)->toDateString(),
            'area_academia' => 1200,
        ])
        ->call('create', true)
        ->assertHasFormErrors([
            'pais_id' => 'required',
            'estado_id' => 'required',
            'cidade_id' => 'required',
        ]);
});
