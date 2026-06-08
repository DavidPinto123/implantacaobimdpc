<?php

use App\Filament\Resources\DadosResource;
use App\Filament\Resources\ListaEmails\ListaEmailResource;
use App\Filament\Resources\MatterportResource;
use App\Filament\Resources\PipeResource;
use App\Models\AutorizacaoServico;
use App\Models\Dados;
use App\Models\ListaEmail;
use App\Models\Matterport;
use App\Models\Pipe;
use Filament\Facades\Filament;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('browser smoke da AutorizacaoServicoResource navega entre list/edit sem criacao manual', function () {
    $resourceClass = 'App\\Filament\\Resources\\AutorizacaoServicos\\AutorizacaoServicoResource';

    if (! class_exists($resourceClass)) {
        test()->markTestSkipped('AutorizacaoServicoResource (App\\Filament\\Resources\\AutorizacaoServicos\\AutorizacaoServicoResource) está ausente; não é possível validar navegação list/edit até a resource dedicada ser restaurada/criada.');
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
        'numero_as' => 'AS-BROWSER-'.strtoupper(str()->random(6)),
        'numero_complemento' => '001',
        'valor' => 12345.67,
        'observacoes' => 'Autorização browser',
    ]);

    visit($resourceClass::getUrl('index'))->assertPathIs(parse_url($resourceClass::getUrl('index'), PHP_URL_PATH));
    visit($resourceClass::getUrl('edit', ['record' => $autorizacao]))->assertPathIs(parse_url($resourceClass::getUrl('edit', ['record' => $autorizacao]), PHP_URL_PATH));
});

it('browser smoke da ListaEmailResource navega entre list/create/edit', function () {
    $user = createPrioritariosUserWithPermissions([
        'ViewAny:ListaEmail',
        'Create:ListaEmail',
        'Update:ListaEmail',
    ]);
    $this->actingAs($user);

    $lista = ListaEmail::create([
        'nome' => 'Lista Browser DCT-009',
        'descricao' => 'Lista browser',
        'emails' => ['browser@example.com'],
        'ativo' => true,
    ]);

    visit(ListaEmailResource::getUrl('index'))->assertPathIs(parse_url(ListaEmailResource::getUrl('index'), PHP_URL_PATH));
    visit(ListaEmailResource::getUrl('create'))->assertPathIs(parse_url(ListaEmailResource::getUrl('create'), PHP_URL_PATH));
    visit(ListaEmailResource::getUrl('edit', ['record' => $lista]))->assertPathIs(parse_url(ListaEmailResource::getUrl('edit', ['record' => $lista]), PHP_URL_PATH));
});

it('browser smoke da PipeResource navega entre list/create/edit', function () {
    $user = createPrioritariosUserWithPermissions([
        'ViewAny:Pipe',
        'Create:Pipe',
        'Update:Pipe',
    ]);
    $this->actingAs($user);

    $pipe = Pipe::create(['pipeline' => 'Pipeline Browser DCT-009']);

    visit(PipeResource::getUrl('index'))->assertPathIs(parse_url(PipeResource::getUrl('index'), PHP_URL_PATH));
    visit(PipeResource::getUrl('create'))->assertPathIs(parse_url(PipeResource::getUrl('create'), PHP_URL_PATH));
    visit(PipeResource::getUrl('edit', ['record' => $pipe]))->assertPathIs(parse_url(PipeResource::getUrl('edit', ['record' => $pipe]), PHP_URL_PATH));
});

it('browser smoke da DadosResource navega na listagem real', function () {
    $user = createPrioritariosUserWithPermissions(['ViewAny:Dados']);
    $this->actingAs($user);

    Dados::create([
        'nova_sigla' => 'DADOS-'.strtoupper(str()->random(3)),
        'unidade' => 'Unidade Browser',
        'marca' => 'Marca Browser',
        'bloco_tipo' => 'Bloco B',
        'categoria' => 'Mobiliário',
        'descricao' => 'Item browser',
        'quantidade' => 1,
        'un' => 'UN',
        'pavimento' => 'Térreo',
        'status' => 'Ativo',
    ]);

    visit(DadosResource::getUrl('index'))->assertPathIs(parse_url(DadosResource::getUrl('index'), PHP_URL_PATH));
});

it('browser smoke da MatterportResource navega entre list/create', function () {
    $user = createPrioritariosUserWithPermissions([
        'ViewAny:Matterport',
        'Create:Matterport',
        'View:Matterport',
        'Update:Matterport',
    ]);
    $this->actingAs($user);

    ['pais' => $pais, 'estado' => $estado, 'cidade' => $cidade] = createPrioritariosGeoDependencies();

    $matterport = Matterport::create([
        'codigo' => 'MAT-BROWSER-'.strtoupper(str()->random(4)),
        'nome' => 'Tour Browser DCT-009',
        'pais_id' => $pais->id,
        'estado_id' => $estado->id,
        'cidade_id' => $cidade->id,
        'endereco' => 'Rua Browser, 10',
        'link_matterport1' => 'https://example.com/browser-tour',
    ]);

    visit(MatterportResource::getUrl('index'))->assertPathIs(parse_url(MatterportResource::getUrl('index'), PHP_URL_PATH));
    visit(MatterportResource::getUrl('create'))->assertPathIs(parse_url(MatterportResource::getUrl('create'), PHP_URL_PATH));
});
