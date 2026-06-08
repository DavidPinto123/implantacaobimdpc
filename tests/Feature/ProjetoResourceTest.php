<?php

use App\Filament\Resources\ProjetoResource\Pages\CreateProjeto;
use App\Filament\Resources\ProjetoResource\Pages\EditProjeto;
use App\Filament\Resources\ProjetoResource\Pages\ListProjetos;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

beforeEach(function () {
    setupAdminPanelForTests();
    ensureDefaultRoles();
});

it('carrega página de listagem de projetos para usuário ativo permitido e exibe um registro de projeto', function () {
    $user = createActiveUserWithPermissions(['ViewAny:Projeto']);
    $this->actingAs($user);

    $projeto = createProjetoRecord($user, ['nome' => 'Projeto Lista']);

    $this->get(projetoResourceUrl('index'))->assertOk();

    Livewire::test(ListProjetos::class)
        ->assertCanSeeTableRecords([$projeto]);
});

it('carrega página de criação de projeto para usuário ativo permitido', function () {
    $user = createActiveUserWithPermissions(['Create:Projeto']);

    $this->actingAs($user)
        ->get(projetoResourceUrl('create'))
        ->assertOk();
});

it('persiste projeto via fallback de modelo no cenário de criação', function () {
    $user = createActiveUserWithPermissions(['Create:Projeto']);
    $user->assignRole('Comercial');

    $recipient = User::factory()->active()->create();
    $recipient->assignRole('Comercial');

    $this->actingAs($user);

    $projeto = createProjetoRecord($user, [
        'nome' => 'Projeto Criado via Livewire',
        'sigla' => 'PCRL',
        'rua' => 'Rua Nova',
        'numero' => '777',
    ]);

    Livewire::test(ListProjetos::class)
        ->assertCanSeeTableRecords([$projeto]);

    $this->assertDatabaseHas('projetos', [
        'nome' => 'Projeto Criado via Livewire',
        'sigla' => 'PCRL',
        'status' => 'Em processo',
        'user_id' => $user->id,
    ]);
});

it('exercita o hook de notificação afterCreate com invocação explícita', function () {
    $user = createActiveUserWithPermissions(['Create:Projeto']);
    $user->assignRole('Comercial');

    $recipient = User::factory()->active()->create();
    $recipient->assignRole('Comercial');

    $this->actingAs($user);

    $projeto = createProjetoRecord($user, ['nome' => 'Projeto Hook Create']);

    $createPage = app(CreateProjeto::class);
    $recordProperty = new ReflectionProperty($createPage, 'record');
    $recordProperty->setAccessible(true);
    $recordProperty->setValue($createPage, $projeto);

    $afterCreateMethod = new ReflectionMethod($createPage, 'afterCreate');
    $afterCreateMethod->setAccessible(true);
    $afterCreateMethod->invoke($createPage);

    $this->assertDatabaseHas('notifications', [
        'notifiable_type' => User::class,
        'notifiable_id' => $user->id,
    ]);

    $this->assertDatabaseHas('notifications', [
        'notifiable_type' => User::class,
        'notifiable_id' => $recipient->id,
    ]);
});

it('carrega página de edição de projeto existente quando usuário é permitido', function () {
    $user = createActiveUserWithPermissions(['Update:Projeto']);
    $this->actingAs($user);

    $projeto = createProjetoRecord($user);

    $this->get(projetoResourceUrl('edit', ['record' => $projeto]))
        ->assertOk();
});

it('atualiza projeto via fallback de modelo e exercita hook de notificação beforeSave da edição', function () {
    $user = createActiveUserWithPermissions(['Update:Projeto']);
    $user->assignRole('Comercial');

    $recipient = User::factory()->active()->create();
    $recipient->assignRole('Comercial');

    $this->actingAs($user);

    $projeto = createProjetoRecord($user, [
        'nome' => 'Projeto Antes',
        'sigla' => 'PANT',
        'status' => 'Em processo',
    ]);

    $editComponent = Livewire::test(EditProjeto::class, ['record' => $projeto->getRouteKey()])
        ->assertSet('data.nome', 'Projeto Antes')
        ->assertSet('data.sigla', 'PANT');

    $beforeSaveMethod = new ReflectionMethod($editComponent->instance(), 'beforeSave');
    $beforeSaveMethod->setAccessible(true);
    $beforeSaveMethod->invoke($editComponent->instance());

    $projeto->update([
        'nome' => 'Projeto Atualizado',
        'sigla' => 'PATU',
        'status' => 'Obras',
    ]);

    $this->assertDatabaseHas('projetos', [
        'id' => $projeto->id,
        'nome' => 'Projeto Atualizado',
        'sigla' => 'PATU',
        'status' => 'Obras',
    ]);

    $this->assertDatabaseHas('notifications', [
        'notifiable_type' => User::class,
        'notifiable_id' => $user->id,
    ]);

    $this->assertDatabaseHas('notifications', [
        'notifiable_type' => User::class,
        'notifiable_id' => $recipient->id,
    ]);
});

it('notifica quais validações bloqueiam o salvamento do projeto', function () {
    $user = createActiveUserWithPermissions(['Update:Projeto']);
    $user->assignRole('Comercial');
    $this->actingAs($user);

    $projeto = createProjetoRecord($user, [
        'data_posse' => null,
        'tipo_imovel' => 'MALL / SHOPPING',
    ]);

    Livewire::test(EditProjeto::class, ['record' => $projeto->getRouteKey()])
        ->call('save')
        ->assertHasFormErrors([
            'data_posse' => 'required',
            'tipo_imovel' => 'in',
        ])
        ->assertSet('validationSummaryMessages', fn (array $messages): bool => in_array('O campo data de Posse é obrigatório.', $messages, true))
        ->assertSee('Para salvar o projeto, corrija os campos abaixo:')
        ->assertSee('O campo data de Posse é obrigatório.');

    Notification::assertNotified('Não foi possível salvar o projeto.');
});

it('carrega página de visualização de projeto existente quando usuário é permitido', function () {
    $user = createActiveUserWithPermissions(['View:Projeto']);
    $this->actingAs($user);

    $projeto = createProjetoRecord($user);

    $this->get(projetoResourceUrl('view', ['record' => $projeto]))
        ->assertOk();
});

it('exclui um projeto através do componente da página de edição do resource', function () {
    $user = createActiveUserWithPermissions(['Delete:Projeto']);
    $this->actingAs($user);

    $projeto = createProjetoRecord($user, ['nome' => 'Projeto Para Excluir']);

    Livewire::test(EditProjeto::class, ['record' => $projeto->getRouteKey()])
        ->callAction('delete');

    $this->assertSoftDeleted('projetos', ['id' => $projeto->id]);
});
