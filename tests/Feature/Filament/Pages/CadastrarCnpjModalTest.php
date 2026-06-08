<?php

use App\Filament\Pages\CadastrarCnpj;
use App\Filament\Pages\ImportCnpjs;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

beforeEach(function (): void {
    setupFilamentResourceCoverageForTests($this);
});

it('permite fechar e reabrir os modais de cadastrar e editar cnpj', function (): void {
    $user = createActiveUserWithPermissions([
        'View:CadastrarCnpj',
        'Create:CadastrarCnpj',
        'Update:CadastrarCnpj',
    ]);
    $this->actingAs($user);

    $projeto = createProjetoRecord($user);

    Livewire::test(CadastrarCnpj::class)
        ->mountTableAction('create')
        ->assertTableActionMounted('create')
        ->unmountTableAction()
        ->assertTableActionNotMounted('create')
        ->mountTableAction('create')
        ->assertTableActionMounted('create')
        ->unmountTableAction()
        ->mountTableAction('edit', $projeto)
        ->assertSet('mountedActions.0.name', 'edit')
        ->assertSet('mountedActions.0.context.recordKey', (string) $projeto->getKey())
        ->unmountTableAction()
        ->assertSet('mountedActions', [])
        ->mountTableAction('edit', $projeto)
        ->assertSet('mountedActions.0.name', 'edit')
        ->assertSet('mountedActions.0.context.recordKey', (string) $projeto->getKey());
});

it('ignora estado residual de cnpj desmontado ao reabrir action da tabela', function (): void {
    $user = createActiveUserWithPermissions([
        'View:CadastrarCnpj',
        'Create:CadastrarCnpj',
    ]);
    $this->actingAs($user);

    Livewire::test(CadastrarCnpj::class)
        ->set('mountedActions.0', [
            'data' => [
                'cnpj' => 'undefined',
                'cnpj_provisorio' => 'undefined',
            ],
        ])
        ->call('mountAction', 'create', [], ['table' => true])
        ->assertSet('mountedActions.0.name', 'create')
        ->assertSet('mountedActions.0.context.table', true)
        ->assertSet('mountedActions.0.data.cnpj', '')
        ->assertSet('mountedActions.0.data.cnpj_provisorio', '');
});

it('delega o stack de modais para a tabela do filament', function (): void {
    expect(file_get_contents(resource_path('views/filament/pages/cadastrar-cnpj.blade.php')))
        ->not->toContain('<x-filament-actions::modals');
});

it('usa a permissão de cadastrar cnpj para exibir e acessar a importação', function (): void {
    $user = createActiveUserWithPermissions([
        'View:CadastrarCnpj',
        'Create:CadastrarCnpj',
    ]);
    $this->actingAs($user);

    Livewire::test(CadastrarCnpj::class)
        ->assertTableActionVisible('import')
        ->assertTableActionVisible('create');

    $this->get(ImportCnpjs::getUrl())->assertOk();
});

it('oculta cadastro e importação de cnpj sem a permissão de cadastrar', function (): void {
    $user = createActiveUserWithPermissions([
        'View:CadastrarCnpj',
    ]);
    $this->actingAs($user);

    Livewire::test(CadastrarCnpj::class)
        ->assertTableActionHidden('import')
        ->assertTableActionHidden('create');

    expect(ImportCnpjs::canAccess())->toBeFalse();
});

it('exige status do cnpj para salvar o cadastro', function (): void {
    $user = createActiveUserWithPermissions([
        'View:CadastrarCnpj',
        'Update:CadastrarCnpj',
    ]);
    $this->actingAs($user);

    $projeto = createProjetoRecord($user, ['cnpj' => null, 'cnpj_provisorio' => null, 'status_cnpj' => null]);

    Livewire::test(CadastrarCnpj::class)
        ->mountTableAction('edit', $projeto)
        ->fillForm([
            'cnpj' => '12.345.678/0001-95',
            'status_cnpj' => null,
        ])
        ->callMountedTableAction()
        ->assertHasFormErrors(['status_cnpj' => 'required']);
});

it('orienta que o cnpj precisa ser preenchido antes do status', function (): void {
    expect(file_get_contents(app_path('Filament/Pages/CadastrarCnpj.php')))
        ->toContain("->helperText('Para selecionar um Status, primeiro preencha o CNPJ.')");
});

it('valida que o status do cnpj tenha o campo correspondente preenchido', function (string $status, array $formData): void {
    $user = createActiveUserWithPermissions([
        'View:CadastrarCnpj',
        'Update:CadastrarCnpj',
    ]);
    $this->actingAs($user);

    $projeto = createProjetoRecord($user, ['cnpj' => null, 'cnpj_provisorio' => null, 'status_cnpj' => null]);

    Livewire::test(CadastrarCnpj::class)
        ->mountTableAction('edit', $projeto)
        ->fillForm([...$formData, 'status_cnpj' => $status])
        ->callMountedTableAction()
        ->assertHasFormErrors(['status_cnpj']);
})->with([
    'definitivo sem cnpj definitivo' => ['definitivo', ['cnpj' => null, 'cnpj_provisorio' => '11.222.333/0001-81']],
    'provisorio sem cnpj provisorio' => ['provisorio', ['cnpj' => '12.345.678/0001-95', 'cnpj_provisorio' => null]],
]);

it('limpa o status quando o campo usado pelo status é removido', function (string $status, string $field): void {
    $user = createActiveUserWithPermissions([
        'View:CadastrarCnpj',
        'Update:CadastrarCnpj',
    ]);
    $this->actingAs($user);

    $projeto = createProjetoRecord($user, [
        'cnpj' => '12.345.678/0001-95',
        'cnpj_provisorio' => '11.222.333/0001-81',
        'status_cnpj' => $status,
    ]);

    Livewire::test(CadastrarCnpj::class)
        ->mountTableAction('edit', $projeto)
        ->assertSet('mountedActions.0.data.status_cnpj', $status)
        ->set("mountedActions.0.data.{$field}", '')
        ->assertSet('mountedActions.0.data.status_cnpj', null);
})->with([
    'definitivo' => ['definitivo', 'cnpj'],
    'provisorio' => ['provisorio', 'cnpj_provisorio'],
]);
