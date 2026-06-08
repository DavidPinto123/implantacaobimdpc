<?php

use App\Filament\Resources\CapexSimulacaos\CapexSimulacaoResource;
use App\Filament\Resources\ControlePedidos\ControlePedidoResource;
use App\Filament\Resources\ElaboracaoAditivos\ElaboracaoAditivoResource;
use App\Models\Asa;
use App\Models\CapexSimulacao;
use App\Models\ControleNotaFiscal;
use App\Models\ControlePedido;
use App\Models\ElaboracaoAditivo;
use App\Models\ElaboracaoAditivoItem;
use App\Models\OrdemInvestimento;
use App\Services\AsaService;
use Database\Factories\ControleNotaFiscalItemFactory;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;

uses(DatabaseTransactions::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Role::findOrCreate('colaborador_orcamento', 'web');
    Role::findOrCreate('engenharia', 'web');
});

it('cobre CRUD básico da ControlePedidoResource com fallback por modelo e geração de itens robusta', function () {
    $user = createPrioritariosUserWithPermissions(['ViewAny:ControlePedido', 'Create:ControlePedido', 'Update:ControlePedido', 'View:ControlePedido']);
    $this->actingAs($user);

    $projeto = createPrioritariosProjeto($user);

    OrdemInvestimento::create([
        'projeto_id' => $projeto->id,
        'valor_total' => 150000,
        'area' => 500,
        'custo_m2' => 300,
        'estrutura' => [
            ['nome' => 'EXECUÇÃO DE OBRA CIVIL - RECHEIO', 'padrao' => 1000, 'ad' => 250],
        ],
    ]);

    $controlePedido = ControlePedido::create([
        'projeto_id' => $projeto->id,
        'status' => 'Em andamento',
        'pedidos' => ['1_1' => true],
    ]);

    ControlePedidoResource::afterSave($controlePedido);

    $controlePedido->update([
        'situacao' => 'Atualizado para teste',
        'pedidos' => ['1_1' => true, '2_1' => false],
    ]);

    ControlePedidoResource::afterSave($controlePedido->fresh());

    $this->get(ControlePedidoResource::getUrl('index'))->assertOk();
    $this->get(ControlePedidoResource::getUrl('create'))->assertOk();
    $this->get(ControlePedidoResource::getUrl('edit', ['record' => $controlePedido]))->assertOk();

    $this->assertDatabaseHas('controle_pedidos', [
        'id' => $controlePedido->id,
        'situacao' => 'Atualizado para teste',
    ]);

    expect($controlePedido->fresh()->itens()->count())->toBe(count(ControlePedidoResource::pedidosMap()));

    $this->assertDatabaseHas('controle_pedido_itens', [
        'controle_pedido_id' => $controlePedido->id,
        'codigo' => '1.1',
        'contratado' => 1,
        'valor' => 1250.00,
    ]);
});

it('cobre CRUD básico da ElaboracaoAditivoResource com fallback por modelo e páginas customizadas', function () {
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

    ElaboracaoAditivoItem::create([
        'elaboracao_aditivo_id' => $aditivo->id,
        'item' => '1.1',
        'descricao_servico' => 'Serviço inicial',
        'quantidade' => 2,
        'unidade' => 'un',
        'valor_material_unitario' => 100,
        'valor_mao_obra_unitario' => 50,
        'total_unitario' => 150,
        'valor_total_geral' => 300,
    ]);

    $aditivo->update(['status_fluxo' => 'aprovacao_gestor']);

    $this->get(ElaboracaoAditivoResource::getUrl('index'))->assertOk();
    $this->get(ElaboracaoAditivoResource::getUrl('create-custom'))->assertOk();
    $this->get(ElaboracaoAditivoResource::getUrl('edit', ['record' => $aditivo]))->assertOk();
    $this->get(ElaboracaoAditivoResource::getUrl('visualizar', ['record' => $aditivo]))->assertOk();

    $this->assertDatabaseHas('elaboracao_aditivos', [
        'id' => $aditivo->id,
        'status_fluxo' => 'aprovacao_gestor',
    ]);
});

it('lista ref servico de aditivo apenas a partir dos itens da obra selecionada', function () {
    $user = createPrioritariosUserWithPermissions([]);
    $this->actingAs($user);

    $obraSelecionada = createObraRecord($user, ['unidade' => 'Obra Ref Serviço A']);
    $outraObra = createObraRecord($user, ['unidade' => 'Obra Ref Serviço B']);

    $controleSelecionado = ControleNotaFiscal::query()->where('obra_id', $obraSelecionada->id)->firstOrFail();
    $controleOutraObra = ControleNotaFiscal::query()->where('obra_id', $outraObra->id)->firstOrFail();

    $escopoSelecionado = createAsEscopoRecord(['escopo' => 'Escopo da obra selecionada']);
    $escopoManualSelecionado = createAsEscopoRecord([
        'escopo' => 'Escopo manual da obra selecionada',
        'is_personalizado' => true,
        'controle_nota_fiscal_id' => $controleSelecionado->id,
    ]);
    $escopoOutraObra = createAsEscopoRecord(['escopo' => 'Escopo de outra obra']);
    $escopoManualOutraObra = createAsEscopoRecord([
        'escopo' => 'Escopo manual de outra obra',
        'is_personalizado' => true,
        'controle_nota_fiscal_id' => $controleOutraObra->id,
    ]);
    $escopoGlobalSemItemNaObra = createAsEscopoRecord(['escopo' => 'Escopo global sem item na obra']);

    ControleNotaFiscalItemFactory::new()->create([
        'controle_nota_fiscal_id' => $controleSelecionado->id,
        'as_escopo_id' => $escopoSelecionado->id,
        'escopo' => $escopoSelecionado->escopo,
    ]);
    ControleNotaFiscalItemFactory::new()->create([
        'controle_nota_fiscal_id' => $controleSelecionado->id,
        'as_escopo_id' => $escopoManualSelecionado->id,
        'escopo' => $escopoManualSelecionado->escopo,
    ]);
    ControleNotaFiscalItemFactory::new()->create([
        'controle_nota_fiscal_id' => $controleOutraObra->id,
        'as_escopo_id' => $escopoOutraObra->id,
        'escopo' => $escopoOutraObra->escopo,
    ]);
    ControleNotaFiscalItemFactory::new()->create([
        'controle_nota_fiscal_id' => $controleOutraObra->id,
        'as_escopo_id' => $escopoManualOutraObra->id,
        'escopo' => $escopoManualOutraObra->escopo,
    ]);

    $opcoes = ElaboracaoAditivoResource::opcoesRefServicoPorObra($obraSelecionada->id);

    expect($opcoes)
        ->toHaveKey($escopoSelecionado->id)
        ->toHaveKey($escopoManualSelecionado->id)
        ->not->toHaveKey($escopoOutraObra->id)
        ->not->toHaveKey($escopoManualOutraObra->id)
        ->not->toHaveKey($escopoGlobalSemItemNaObra->id);
});

it('cria ASA a partir do aditivo usando o gestor selecionado no aditivo', function () {
    $user = createPrioritariosUserWithPermissions([]);
    $this->actingAs($user);

    $gestorSelecionado = createPrioritariosUserWithPermissions([]);
    $obra = createObraRecord($user, ['engenharia' => 'Nome divergente da obra']);
    $escopo = createAsEscopoRecord();

    $aditivo = ElaboracaoAditivo::create([
        'user_id' => $user->id,
        'obra_id' => $obra->id,
        'gestor_id' => $gestorSelecionado->id,
        'as_escopo_id' => $escopo->id,
        'data' => now()->toDateString(),
        'status_fluxo' => 'aprovacao_gestor',
        'foto_antes' => [],
        'foto_depois' => [],
        'projeto_orcado' => [],
        'projeto_revisado' => [],
        'escopo_contratado' => [],
        'escopo_real' => [],
    ]);

    ElaboracaoAditivoItem::create([
        'elaboracao_aditivo_id' => $aditivo->id,
        'item' => '1.1',
        'descricao_servico' => 'Serviço inicial',
        'quantidade' => 2,
        'unidade' => 'un',
        'valor_material_unitario' => 100,
        'valor_mao_obra_unitario' => 50,
        'total_unitario' => 150,
        'valor_total_geral' => 300,
    ]);

    $asa = app(AsaService::class)->criarAPartirDoAditivo($aditivo->fresh([
        'obra',
        'gestor',
        'construtora',
        'asEscopo',
        'itens',
        'user',
    ]), 'Justificativa teste');

    expect($asa)
        ->toBeInstanceOf(Asa::class)
        ->and($asa->gestor_id)->toBe($gestorSelecionado->id)
        ->and($asa->fresh('gestor')->gestor?->name)->toBe($gestorSelecionado->name);
});

it('cobre CRUD básico da CapexSimulacaoResource com fallback por modelo e páginas principais', function () {
    $user = createPrioritariosUserWithPermissions(['ViewAny:CapexSimulacao', 'Create:CapexSimulacao', 'Update:CapexSimulacao', 'View:CapexSimulacao']);
    $this->actingAs($user);

    $projeto = createPrioritariosProjeto($user);
    $faixa = createAsFaixaAreaRecord(['area_min' => 200, 'area_max' => 400]);

    $simulacao = CapexSimulacao::create([
        'projeto_id' => $projeto->id,
        'nome' => 'Simulação CAPEX Teste',
        'sigla' => 'CPT',
        'endereco' => 'Rua Teste 123',
        'uf' => 'SP',
        'area_unidade' => 250,
        'fator_correcao' => 1.15,
        'as_faixa_area_id' => $faixa->id,
        'faixa_nome' => $faixa->nome,
        'status' => 1,
        'comentario' => 'Versão inicial',
    ]);

    $simulacao->update(['comentario' => 'Versão atualizada']);

    $this->get(CapexSimulacaoResource::getUrl('index'))->assertOk();
    $this->get(CapexSimulacaoResource::getUrl('create'))->assertOk();
    $this->get(CapexSimulacaoResource::getUrl('edit', ['record' => $simulacao]))->assertOk();

    $this->assertDatabaseHas('capex_simulacoes', [
        'id' => $simulacao->id,
        'comentario' => 'Versão atualizada',
    ]);
});
