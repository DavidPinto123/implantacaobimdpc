<?php

use App\Filament\Resources\AutorizacaoServicos\Schemas\AutorizacaoServicoForm;
use App\Filament\Resources\DadosResource;
use App\Filament\Resources\ListaEmails\ListaEmailResource;
use App\Filament\Resources\MatterportResource;
use App\Filament\Resources\PipeResource;
use App\Models\AsEscopo;
use App\Models\AutorizacaoServico;
use App\Models\Construtora;
use App\Models\Dados;
use App\Models\ListaEmail;
use App\Models\Matterport;
use App\Models\Obras;
use App\Models\Pipe;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('usa o controle de as como list da AutorizacaoServicoResource', function () {
    $resourceClass = 'App\\Filament\\Resources\\AutorizacaoServicos\\AutorizacaoServicoResource';

    if (! class_exists($resourceClass)) {
        test()->markTestSkipped('AutorizacaoServicoResource (App\\Filament\\Resources\\AutorizacaoServicos\\AutorizacaoServicoResource) está ausente; cobertura de rotas list/edit permanece pendente.');
    }

    ensureDefaultRoles();

    $user = createPrioritariosUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'View:AutorizacaoServico',
        'Create:AutorizacaoServico',
        'Update:AutorizacaoServico',
    ]);
    setGestorObras($user);
    $this->actingAs($user);

    $obra = createObraRecord($user);
    $escopo = createAsEscopoRecord();
    $construtora = createConstrutoraRecord();

    $autorizacao = AutorizacaoServico::create([
        'obra_id' => $obra->id,
        'as_escopo_id' => $escopo->id,
        'construtora_id' => $construtora->id,
        'numero_as' => 'AS-RESOURCE-'.strtoupper(str()->random(6)),
        'numero_complemento' => '001',
        'valor' => 12345.67,
        'observacoes' => 'Autorização para cobertura de páginas',
    ]);

    expect($resourceClass::getModel())->toBe(AutorizacaoServico::class);
    expect($resourceClass::getNavigationLabel())->toBe('Controle de AS')
        ->and($resourceClass::getNavigationGroup())->toBe('Expansão')
        ->and($resourceClass::getNavigationParentItem())->toBe('Orçamentos');

    $this->get($resourceClass::getUrl('index'))
        ->assertOk()
        ->assertSee('Controle de AS');

    expect($resourceClass::getPages())->not->toHaveKey('create');
    expect($resourceClass::getPages())->not->toHaveKey('controle');

    $this->get('/admin/autorizacoes-servico/create')->assertNotFound();
    $this->get('/admin/autorizacoes-servico/controle')->assertNotFound();
    $this->get($resourceClass::getUrl('edit', ['record' => $autorizacao]))
        ->assertOk()
        ->assertSee('Dados do sistema')
        ->assertDontSee('Tipo da contratação')
        ->assertSee('Criado por')
        ->assertSee('Enviado em')
        ->assertSee('Cancelado em')
        ->assertSee('Motivo do cancelamento');
});

it('mantém labels textuais nos selects de AS quando o campo exibido está vazio', function () {
    expect(AutorizacaoServicoForm::obraOptionLabel(new Obras(['unidade' => null])))->toBe('Unidade sem identificação')
        ->and(AutorizacaoServicoForm::asEscopoOptionLabel(new AsEscopo([
            'escopo' => null,
            'numero_as' => 'AS-FALLBACK',
        ])))->toBe('AS-FALLBACK')
        ->and(AutorizacaoServicoForm::construtoraOptionLabel(new Construtora(['nome' => null])))->toBe('Fornecedor sem identificação')
        ->and(AutorizacaoServicoForm::usuarioOptionLabel(new User([
            'name' => null,
            'email' => 'usuario@example.com',
        ])))->toBe('usuario@example.com');
});

it('cobre persistência básica de AutorizacaoServico por modelo', function () {
    ensureDefaultRoles();

    $user = createPrioritariosUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'Create:AutorizacaoServico',
        'Update:AutorizacaoServico',
    ]);
    setGestorObras($user);
    $this->actingAs($user);

    $obra = createObraRecord($user);
    $escopo = createAsEscopoRecord();
    $construtora = createConstrutoraRecord();

    $autorizacao = AutorizacaoServico::create([
        'obra_id' => $obra->id,
        'as_escopo_id' => $escopo->id,
        'construtora_id' => $construtora->id,
        'numero_as' => 'AS-DCT-'.strtoupper(str()->random(6)),
        'numero_complemento' => '001',
        'valor' => 12345.67,
        'observacoes' => 'Autorização criada em teste',
    ]);

    $autorizacao->update([
        'numero_complemento' => '002',
        'observacoes' => 'Autorização atualizada em teste',
    ]);

    $this->assertDatabaseHas('autorizacao_servicos', [
        'id' => $autorizacao->id,
        'numero_complemento' => '002',
        'observacoes' => 'Autorização atualizada em teste',
    ]);
});

it('cobre CRUD básico da ListaEmailResource com persistência por modelo e páginas principais', function () {
    $user = createPrioritariosUserWithPermissions([
        'ViewAny:ListaEmail',
        'Create:ListaEmail',
        'Update:ListaEmail',
    ]);
    $this->actingAs($user);

    $lista = ListaEmail::create([
        'nome' => 'Lista DCT-009',
        'descricao' => 'Lista inicial',
        'emails' => ['time@example.com'],
        'ativo' => true,
    ]);

    $lista->update([
        'descricao' => 'Lista atualizada',
        'emails' => ['time@example.com', 'ops@example.com'],
    ]);

    $this->get(ListaEmailResource::getUrl('index'))->assertOk();
    $this->get(ListaEmailResource::getUrl('create'))->assertOk();
    $this->get(ListaEmailResource::getUrl('edit', ['record' => $lista]))->assertOk();

    $this->assertDatabaseHas('lista_emails', [
        'id' => $lista->id,
        'descricao' => 'Lista atualizada',
    ]);
});

it('cobre CRUD básico da PipeResource com persistência por modelo e páginas principais', function () {
    $user = createPrioritariosUserWithPermissions([
        'ViewAny:Pipe',
        'Create:Pipe',
        'Update:Pipe',
    ]);
    $this->actingAs($user);

    $pipe = Pipe::create(['pipeline' => 'Pipeline Inicial DCT-009']);
    $pipe->update(['pipeline' => 'Pipeline Atualizado DCT-009']);

    $this->get(PipeResource::getUrl('index'))->assertOk();
    $this->get(PipeResource::getUrl('create'))->assertOk();
    $this->get(PipeResource::getUrl('edit', ['record' => $pipe]))->assertOk();

    $this->assertDatabaseHas('pipes', [
        'id' => $pipe->id,
        'pipeline' => 'Pipeline Atualizado DCT-009',
    ]);
});

it('cobre fluxo básico da DadosResource com persistência por modelo e página index real', function () {
    $user = createPrioritariosUserWithPermissions(['ViewAny:Dados']);
    $this->actingAs($user);

    $dados = Dados::create([
        'nova_sigla' => 'NS-'.strtoupper(str()->random(4)),
        'unidade' => 'Unidade DCT',
        'marca' => 'Marca DCT',
        'bloco_tipo' => 'Bloco A',
        'categoria' => 'Equipamento',
        'descricao' => 'Item para listagem',
        'quantidade' => 5,
        'un' => 'UN',
        'pavimento' => '1',
        'status' => 'Ativo',
    ]);

    $dados->update(['descricao' => 'Item atualizado para listagem']);

    $this->get(DadosResource::getUrl('index'))->assertOk();

    $this->assertDatabaseHas('dados', [
        'id' => $dados->id,
        'descricao' => 'Item atualizado para listagem',
    ]);
});

it('cobre CRUD básico da MatterportResource com persistência por modelo e páginas estáveis', function () {
    $user = createPrioritariosUserWithPermissions([
        'ViewAny:Matterport',
        'Create:Matterport',
        'View:Matterport',
        'Update:Matterport',
    ]);
    $this->actingAs($user);

    ['pais' => $pais, 'estado' => $estado, 'cidade' => $cidade] = createPrioritariosGeoDependencies();

    $matterport = Matterport::create([
        'codigo' => 'MAT-'.strtoupper(str()->random(6)),
        'nome' => 'Tour DCT-009',
        'sigla' => 'TDCT',
        'nova_sigla' => 'TDCT-N',
        'pais_id' => $pais->id,
        'estado_id' => $estado->id,
        'cidade_id' => $cidade->id,
        'endereco' => 'Rua Teste 100',
        'link_matterport1' => 'https://example.com/matterport-1',
    ]);

    $matterport->update([
        'nome' => 'Tour DCT-009 Atualizado',
        'link_google_maps' => 'https://maps.example.com/local',
    ]);

    $this->get(MatterportResource::getUrl('index'))->assertOk();
    $this->get(MatterportResource::getUrl('create'))->assertOk();

    $this->assertDatabaseHas('matterports', [
        'id' => $matterport->id,
        'nome' => 'Tour DCT-009 Atualizado',
    ]);
});
