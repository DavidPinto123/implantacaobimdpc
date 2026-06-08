<?php

use App\Filament\Pages\CadastrarCnpj;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    ensureDefaultRoles();
});

it('oculta importar planilha quando usuario nao pode criar cnpj', function () {
    $user = createActiveUserWithPermissions(['View:CadastrarCnpj']);

    $this->actingAs($user);

    Livewire::test(CadastrarCnpj::class)
        ->assertTableActionHidden('import')
        ->assertTableActionHidden('create');
});

it('exibe importar planilha quando usuario pode criar cnpj', function () {
    $user = createActiveUserWithPermissions([
        'View:CadastrarCnpj',
        'Create:CadastrarCnpj',
    ]);

    $this->actingAs($user);

    Livewire::test(CadastrarCnpj::class)
        ->assertTableActionVisible('import')
        ->assertTableActionVisible('create');
});
