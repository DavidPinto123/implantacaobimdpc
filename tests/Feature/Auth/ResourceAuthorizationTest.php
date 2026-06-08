<?php

use App\Filament\Resources\ControleNotaFiscals\ControleNotaFiscalResource;
use App\Filament\Resources\ControleNotaFiscals\Pages\EditControleNotaFiscal;
use App\Filament\Resources\ControlePedidos\ControlePedidoResource;
use App\Filament\Resources\Obras\ObrasResource;
use App\Filament\Resources\PosObra\PendenciaResource;
use App\Filament\Resources\ProjetoResource;
use App\Filament\Resources\Tasks\TaskResource;
use App\Filament\Resources\UserResource;
use App\Models\ControlePedido;
use App\Models\Marca;
use App\Models\PosObra\Pendencia;
use App\Models\Task;
use App\Models\TaskCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(DatabaseTransactions::class);

beforeEach(function () {
    setupAdminPanelForTests();
    ensureDefaultRoles();
    Role::findOrCreate('Fornecedor', 'web');
});

it('bloqueia UserResource sem ViewAny/Create/Update', function () {
    $user = createActiveUserWithPermissions([]);
    $target = User::factory()->active()->create();

    $this->actingAs($user);

    $this->get(UserResource::getUrl('index'))->assertForbidden();
    $this->get(UserResource::getUrl('create'))->assertForbidden();
    $this->get(UserResource::getUrl('edit', ['record' => $target]))->assertForbidden();
});

it('bloqueia ObrasResource sem ViewAny/Create/Update', function () {
    $user = createResourceBaselineUser([]);
    $creator = createActiveUserWithPermissions(['ViewAny:Obras', 'Create:Obras', 'Update:Obras']);

    $this->actingAs($creator);
    $obra = createObraRecord($creator);

    $this->actingAs($user);

    $this->get(ObrasResource::getUrl('index'))->assertForbidden();
    $this->get(ObrasResource::getUrl('create'))->assertForbidden();
    $this->get(ObrasResource::getUrl('edit', ['record' => $obra]))->assertForbidden();
});

it('bloqueia ObrasResource para role Fornecedor mesmo com permissões', function () {
    $user = createResourceBaselineUser(['ViewAny:Obras', 'Create:Obras', 'Update:Obras']);
    $user->assignRole('Fornecedor');

    $this->actingAs($user);
    $obra = createObraRecord($user);

    $this->get(ObrasResource::getUrl('index'))->assertForbidden();
    $this->get(ObrasResource::getUrl('create'))->assertForbidden();
    $this->get(ObrasResource::getUrl('edit', ['record' => $obra]))->assertForbidden();
});

it('bloqueia ControlePedidoResource sem ViewAny/Create/Update', function () {
    $user = createPrioritariosUserWithPermissions([]);
    $creator = createPrioritariosUserWithPermissions([]);
    $this->actingAs($creator);
    $projeto = createPrioritariosProjeto($creator);
    $controlePedido = ControlePedido::create([
        'projeto_id' => $projeto->id,
        'status' => 'definitivo',
        'situacao' => 'em_processo',
        'valor_oi' => 100000,
        'valor_realizado' => 25000,
        'realizado_nf' => 10000,
        'saldo' => 75000,
    ]);

    $this->actingAs($user);

    $this->get(ControlePedidoResource::getUrl('index'))->assertForbidden();
    $this->get(ControlePedidoResource::getUrl('create'))->assertForbidden();
    $this->get(ControlePedidoResource::getUrl('edit', ['record' => $controlePedido]))->assertForbidden();
});

it('bloqueia ControleNotaFiscalResource sem ViewAny/Create/Update', function () {
    $user = createFinanceiroUserWithPermissions([]);
    $creator = createFinanceiroUserWithPermissions([]);
    $this->actingAs($creator);
    ['controle' => $controle] = createControleNotaFiscalComNota($creator);

    $this->actingAs($user);

    $this->get(ControleNotaFiscalResource::getUrl('index'))->assertForbidden();
    $this->get('/admin/controle-notas-fiscais/create')->assertNotFound();
    $this->get(ControleNotaFiscalResource::getUrl('edit', ['record' => $controle]))->assertNotFound();
});

it('bloqueia delete em ControleNotaFiscalResource sem permissão Delete', function () {
    $user = createFinanceiroUserWithPermissions([
        'ViewAny:ControleNotaFiscal',
        'Update:ControleNotaFiscal',
    ]);
    $this->actingAs($user);
    ['controle' => $controle] = createControleNotaFiscalComNota($user);

    $this->actingAs($user);

    expect(fn () => Livewire::test(EditControleNotaFiscal::class, ['record' => $controle->getRouteKey()])
        ->callAction('delete'))
        ->toThrow(ModelNotFoundException::class);
});

it('bloqueia PendenciaResource sem ViewAny/Create/View/Update', function () {
    $user = createPrioritariosUserWithPermissions([]);
    $gestor = createPrioritariosUserWithPermissions([]);
    $this->actingAs($gestor);
    $obra = createObraRecord($gestor);
    $pendencia = Pendencia::create([
        'obras_id' => $obra->id,
        'gestor_id' => $gestor->id,
        'descricao' => 'Pendência auth neg',
        'urgencia' => 'P2',
        'status' => 'REGISTRADA',
    ]);

    $this->actingAs($user);

    $this->get(PendenciaResource::getUrl('index'))->assertForbidden();
    $this->get(PendenciaResource::getUrl('create'))->assertForbidden();
    $this->get(PendenciaResource::getUrl('view', ['record' => $pendencia]))->assertForbidden();
    $this->get(PendenciaResource::getUrl('edit', ['record' => $pendencia]))->assertForbidden();
});

it('bloqueia TaskResource sem ViewAny', function () {
    $user = createTaskManagementUser([]);

    $this->actingAs($user)
        ->get(TaskResource::getUrl('index'))
        ->assertForbidden();
});

it('retorna 404 para Task fora do escopo de setor', function () {
    $user = createTaskManagementUser(['ViewAny:Task', 'View:Task']);
    $setorPermitido = attachUserToSetor($user, 'Setor Permitido AuthNeg');
    $setorNaoPermitido = attachUserToSetor(createTaskManagementUser([], 'Colaborador'), 'Setor Restrito AuthNeg');

    $categoria = TaskCategory::create(['name' => 'Categoria Auth Neg']);
    $marca = Marca::create(['nome' => 'Marca Auth Neg']);
    $responsavel = createTaskManagementUser([], 'Colaborador');
    $responsavel->setores()->syncWithoutDetaching([$setorPermitido->id]);

    $this->actingAs($responsavel);

    $taskNaoVisivel = Task::create([
        'title' => 'Tarefa fora do escopo auth-neg',
        'description' => 'Não pode ser acessada',
        'task_category_id' => $categoria->id,
        'sigla' => 'AUTHNEG',
        'marca_id' => $marca->id,
        'setor_id' => $setorNaoPermitido->id,
        'assigned_to' => $responsavel->id,
        'inicio' => now()->toDateString(),
        'prazo' => 2,
        'dias_corridos' => true,
        'status' => 'pendente',
        'created_by' => $responsavel->id,
    ]);

    $this->actingAs($user)
        ->get(TaskResource::getUrl('view', ['record' => $taskNaoVisivel]))
        ->assertNotFound();
});

it('mantém índice do ProjetoResource acessível para usuário ativo sem ViewAny', function () {
    $user = createActiveUserWithPermissions([]);

    $this->actingAs($user)
        ->get(projetoResourceUrl('index'))
        ->assertOk();
});

it('bloqueia usuário com role Comercial sem Create/Delete em ProjetoResource', function () {
    $user = createActiveUserWithPermissions(['ViewAny:Projeto', 'View:Projeto', 'Update:Projeto']);
    $user->assignRole('Comercial');

    $this->actingAs($user);
    $projeto = createProjetoRecord($user, ['nome' => 'Projeto Auth Neg Comercial']);

    $this->get(projetoResourceUrl('create'))->assertForbidden();

    expect(ProjetoResource::canDelete($projeto))->toBeFalse();
});
