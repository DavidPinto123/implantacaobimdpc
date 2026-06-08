<?php

use App\Filament\Resources\CapexSimulacaos\CapexSimulacaoResource;
use App\Filament\Resources\ControlePedidos\ControlePedidoResource;
use App\Filament\Resources\ElaboracaoAditivos\ElaboracaoAditivoResource;
use App\Models\CapexSimulacao;
use App\Models\ControlePedido;
use App\Models\ElaboracaoAditivo;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Role::findOrCreate('colaborador_orcamento', 'web');
    Role::findOrCreate('engenharia', 'web');
});

it('browser smoke da ControlePedidoResource navega entre list/create/edit com estabilidade', function () {
    $user = createPrioritariosUserWithPermissions(['ViewAny:ControlePedido', 'Create:ControlePedido', 'Update:ControlePedido']);
    $this->actingAs($user);

    $projeto = createPrioritariosProjeto($user);
    $controlePedido = ControlePedido::create([
        'projeto_id' => $projeto->id,
        'pedidos' => ['1_1' => true],
    ]);

    visit(ControlePedidoResource::getUrl('index'))->assertPathIs('/admin/controle-pedidos');
    visit(ControlePedidoResource::getUrl('create'))->assertPathIs('/admin/controle-pedidos/create');
    visit(ControlePedidoResource::getUrl('edit', ['record' => $controlePedido]))->assertPathIs("/admin/controle-pedidos/{$controlePedido->id}/edit");
});

it('browser smoke da ElaboracaoAditivoResource navega entre páginas customizadas com estabilidade', function () {
    $user = createPrioritariosUserWithPermissions(['ViewAny:ElaboracaoAditivo', 'Create:ElaboracaoAditivo', 'Update:ElaboracaoAditivo', 'View:ElaboracaoAditivo']);
    $this->actingAs($user);

    $gestor = createPrioritariosUserWithPermissions([]);
    $obra = createObraRecord($user, ['engenharia' => $gestor->name]);
    $escopo = createAsEscopoRecord();

    $aditivo = ElaboracaoAditivo::create([
        'user_id' => $user->id,
        'obra_id' => $obra->id,
        'gestor_id' => $gestor->id,
        'as_escopo_id' => $escopo->id,
        'data' => now()->toDateString(),
        'status_fluxo' => 'elaboracao',
        'foto_antes' => [],
        'foto_depois' => [],
        'projeto_orcado' => [],
        'projeto_revisado' => [],
        'escopo_contratado' => [],
        'escopo_real' => [],
    ]);

    visit(ElaboracaoAditivoResource::getUrl('index'))->assertPathIs('/admin/elaboracao-aditivos');
    visit(ElaboracaoAditivoResource::getUrl('create-custom'))->assertPathIs('/admin/elaboracao-aditivos/criar');
    visit(ElaboracaoAditivoResource::getUrl('edit', ['record' => $aditivo]))->assertPathIs("/admin/elaboracao-aditivos/{$aditivo->id}/edit");
    visit(ElaboracaoAditivoResource::getUrl('visualizar', ['record' => $aditivo]))->assertPathIs("/admin/elaboracao-aditivos/{$aditivo->id}/visualizar");
});

it('browser smoke da CapexSimulacaoResource navega entre list/create/edit com estabilidade', function () {
    $user = createPrioritariosUserWithPermissions(['ViewAny:CapexSimulacao', 'Create:CapexSimulacao', 'Update:CapexSimulacao']);
    $this->actingAs($user);

    $projeto = createPrioritariosProjeto($user);
    $simulacao = CapexSimulacao::create([
        'projeto_id' => $projeto->id,
        'nome' => 'Simulação Browser',
        'sigla' => 'SBR',
        'endereco' => 'Rua Browser 123',
        'uf' => 'SP',
        'area_unidade' => 300,
        'fator_correcao' => 1.10,
        'status' => 1,
    ]);

    visit(CapexSimulacaoResource::getUrl('index'))->assertPathIs('/admin/capex-simulacaos');
    visit(CapexSimulacaoResource::getUrl('create'))->assertPathIs('/admin/capex-simulacaos/create');
    visit(CapexSimulacaoResource::getUrl('edit', ['record' => $simulacao]))->assertPathIs("/admin/capex-simulacaos/{$simulacao->id}/edit");
});
