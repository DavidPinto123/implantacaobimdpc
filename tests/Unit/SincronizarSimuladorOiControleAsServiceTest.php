<?php

use App\Enums\AsStatus;
use App\Enums\TipoUnidade;
use App\Models\AsEscopo;
use App\Models\AutorizacaoServico;
use App\Models\CapexSimulacao;
use App\Models\CapexSimulacaoItem;
use App\Models\Construtora;
use App\Models\ControleNotaFiscal;
use App\Models\ControleNotaFiscalItem;
use App\Models\Obras;
use App\Services\SincronizarSimuladorOiControleAsService;
use Database\Factories\AsEscopoFactory;
use Database\Factories\ObrasFactory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

uses(TestCase::class, DatabaseTransactions::class);

it('preenche valor estimado por escopo e complemento do simulador oi', function () {
    $obra = ObrasFactory::new()->create();
    $controle = controleNotaFiscalExpansaoParaSincronizacao($obra, [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'unidade' => $obra->unidade,
    ]);
    $escopo = AsEscopoFactory::new()->create([
        'grupo' => 'Shell',
        'numero_as' => '01.1',
        'escopo' => 'Shell',
    ]);
    $simulacao = CapexSimulacao::create([
        'projeto_id' => $obra->projeto_id,
        'nome' => 'Simulacao OI',
        'area_unidade' => 100,
        'fator_correcao' => 1,
        'status' => 1,
    ]);
    $itemPrincipalSimulador = CapexSimulacaoItem::create([
        'capex_simulacao_id' => $simulacao->id,
        'as_escopo_id' => $escopo->id,
        'numero_complemento' => '',
        'tipo' => 'auto',
        'incluir' => true,
        'nome_escopo' => 'Shell principal',
        'valor_base_m2' => 10,
        'area' => 100,
        'fator_correcao' => 1,
        'custo_estimado' => 1000,
        'percentual' => 80,
    ]);
    $itemC1Simulador = CapexSimulacaoItem::create([
        'capex_simulacao_id' => $simulacao->id,
        'as_escopo_id' => $escopo->id,
        'numero_complemento' => 'C1',
        'tipo' => 'auto',
        'incluir' => true,
        'nome_escopo' => 'Shell complemento',
        'valor_base_m2' => 2.5,
        'area' => 100,
        'fator_correcao' => 1,
        'custo_estimado' => 250,
        'percentual' => 20,
    ]);
    $linhaPrincipal = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $escopo->id,
        'numero_complemento' => null,
        'percentual_total' => 100,
    ]);
    $linhaC1 = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $escopo->id,
        'numero_complemento' => 'C1',
        'percentual_total' => 100,
    ]);

    $resultado = app(SincronizarSimuladorOiControleAsService::class)
        ->sincronizar($obra, $simulacao);

    expect($resultado['preenchidos'])->toBe(2)
        ->and($linhaPrincipal->refresh()->valor_estimado_as)->toBe('1000.00')
        ->and($linhaPrincipal->valor_estimado_as_simulador)->toBe('1000.00')
        ->and($linhaPrincipal->capex_simulacao_item_id)->toBe($itemPrincipalSimulador->id)
        ->and($linhaC1->refresh()->valor_estimado_as)->toBe('250.00')
        ->and($linhaC1->valor_estimado_as_simulador)->toBe('250.00')
        ->and($linhaC1->capex_simulacao_item_id)->toBe($itemC1Simulador->id);
});

it('sobrescreve valor estimado editado manualmente', function () {
    $obra = ObrasFactory::new()->create();
    $controle = controleNotaFiscalExpansaoParaSincronizacao($obra, [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'unidade' => $obra->unidade,
    ]);
    $escopo = AsEscopoFactory::new()->create();
    $simulacao = CapexSimulacao::create([
        'projeto_id' => $obra->projeto_id,
        'nome' => 'Simulacao Manual',
        'area_unidade' => 100,
        'fator_correcao' => 1,
        'status' => 1,
    ]);
    CapexSimulacaoItem::create([
        'capex_simulacao_id' => $simulacao->id,
        'as_escopo_id' => $escopo->id,
        'numero_complemento' => '',
        'tipo' => 'auto',
        'incluir' => true,
        'nome_escopo' => 'Escopo manual',
        'valor_base_m2' => 10,
        'area' => 100,
        'fator_correcao' => 1,
        'custo_estimado' => 1000,
        'percentual' => 100,
    ]);
    $linha = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $escopo->id,
        'numero_complemento' => null,
        'valor_estimado_as' => 1200,
        'valor_estimado_as_simulador' => 1000,
        'valor_estimado_as_editado_manualmente' => true,
        'percentual_total' => 100,
    ]);

    $resultado = app(SincronizarSimuladorOiControleAsService::class)
        ->sincronizar($obra, $simulacao);

    expect($resultado['ignorados_edicao_manual'])->toBe(0)
        ->and($resultado['preenchidos'])->toBe(1)
        ->and($linha->refresh()->valor_estimado_as)->toBe('1000.00')
        ->and($linha->valor_estimado_as_simulador)->toBe('1000.00')
        ->and($linha->valor_estimado_as_editado_manualmente)->toBeFalse();
});

it('nao preenche linha vinculada a as cancelada ao sincronizar simulador oi', function () {
    $obra = ObrasFactory::new()->create();
    $controle = controleNotaFiscalExpansaoParaSincronizacao($obra, [
        'status' => ControleNotaFiscal::STATUS_RASCUNHO,
        'data_base' => now()->toDateString(),
        'unidade' => $obra->unidade,
    ]);
    $escopo = AsEscopoFactory::new()->create([
        'grupo' => 'Civil',
        'numero_as' => '98.1',
        'escopo' => 'Escopo cancelado',
    ]);
    $fornecedor = Construtora::create([
        'nome' => 'Fornecedor AS Cancelada',
        'cnpj' => '44.444.444/0001-44',
        'tipo' => 'CONSTRUTORA',
    ]);
    $autorizacaoServico = AutorizacaoServico::create([
        'obra_id' => $obra->id,
        'as_escopo_id' => $escopo->id,
        'construtora_id' => $fornecedor->id,
        'status' => AsStatus::CANCELADA,
        'numero_as' => 'AS-CANCELADA-SYNC',
        'valor' => 100,
        'valor_estimado' => 100,
    ]);
    $linha = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $escopo->id,
        'numero_complemento' => null,
        'valor_estimado_as' => 0,
        'percentual_total' => 100,
    ]);
    $autorizacaoServico->forceFill(['controle_nota_fiscal_item_id' => $linha->id])->save();
    $simulacao = CapexSimulacao::create([
        'projeto_id' => $obra->projeto_id,
        'nome' => 'Simulacao OI AS Cancelada',
        'area_unidade' => 100,
        'fator_correcao' => 1,
        'status' => 1,
    ]);
    CapexSimulacaoItem::create([
        'capex_simulacao_id' => $simulacao->id,
        'as_escopo_id' => $escopo->id,
        'numero_complemento' => '',
        'tipo' => 'auto',
        'incluir' => true,
        'nome_escopo' => 'Escopo cancelado',
        'valor_base_m2' => 999.99,
        'area' => 100,
        'fator_correcao' => 1,
        'custo_estimado' => 999.99,
        'percentual' => 100,
    ]);

    $resultado = app(SincronizarSimuladorOiControleAsService::class)
        ->sincronizar($obra, $simulacao);

    expect($resultado['preenchidos'])->toBe(0)
        ->and(implode(' ', $resultado['conflitos']))->toContain('Linha vinculada a AS cancelada ignorada')
        ->and($linha->refresh()->valor_estimado_as)->toBe('0.00')
        ->and($linha->capex_simulacao_item_id)->toBeNull();
});

it('preenche linha vinculada a as criada ao sincronizar simulador oi', function () {
    $obra = ObrasFactory::new()->create();
    $controle = controleNotaFiscalExpansaoParaSincronizacao($obra, [
        'status' => ControleNotaFiscal::STATUS_RASCUNHO,
        'data_base' => now()->toDateString(),
        'unidade' => $obra->unidade,
    ]);
    $escopo = AsEscopoFactory::new()->create([
        'grupo' => 'Civil',
        'numero_as' => '98.2',
        'escopo' => 'Escopo com AS criada',
    ]);
    $fornecedor = Construtora::create([
        'nome' => 'Fornecedor AS Criada',
        'cnpj' => '55.555.555/0001-55',
        'tipo' => 'CONSTRUTORA',
    ]);
    $autorizacaoServico = AutorizacaoServico::create([
        'obra_id' => $obra->id,
        'as_escopo_id' => $escopo->id,
        'construtora_id' => $fornecedor->id,
        'status' => AsStatus::CRIADA,
        'numero_as' => 'AS-CRIADA-SYNC',
        'valor' => 100,
        'valor_estimado' => 100,
    ]);
    $linha = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $escopo->id,
        'numero_complemento' => null,
        'valor_estimado_as' => 100,
        'percentual_total' => 100,
    ]);
    $autorizacaoServico->forceFill(['controle_nota_fiscal_item_id' => $linha->id])->save();
    $simulacao = CapexSimulacao::create([
        'projeto_id' => $obra->projeto_id,
        'nome' => 'Simulacao OI AS Criada',
        'area_unidade' => 100,
        'fator_correcao' => 1,
        'status' => 1,
    ]);
    CapexSimulacaoItem::create([
        'capex_simulacao_id' => $simulacao->id,
        'as_escopo_id' => $escopo->id,
        'numero_complemento' => '',
        'tipo' => 'auto',
        'incluir' => true,
        'nome_escopo' => 'Escopo com AS criada',
        'valor_base_m2' => 999.99,
        'area' => 100,
        'fator_correcao' => 1,
        'custo_estimado' => 999.99,
        'percentual' => 100,
    ]);

    $resultado = app(SincronizarSimuladorOiControleAsService::class)
        ->sincronizar($obra, $simulacao);

    expect($resultado['preenchidos'])->toBe(1)
        ->and($resultado['conflitos'])->toBe([])
        ->and($linha->refresh()->valor_estimado_as)->toBe('999.99')
        ->and($linha->capex_simulacao_item_id)->not->toBeNull()
        ->and($autorizacaoServico->refresh()->valor_estimado)->toBe('999.99')
        ->and($autorizacaoServico->valor)->toBe('100.00');
});

it('nao preenche linha vinculada a as enviada ao sincronizar simulador oi', function () {
    $obra = ObrasFactory::new()->create();
    $controle = controleNotaFiscalExpansaoParaSincronizacao($obra, [
        'status' => ControleNotaFiscal::STATUS_RASCUNHO,
        'data_base' => now()->toDateString(),
        'unidade' => $obra->unidade,
    ]);
    $escopo = AsEscopoFactory::new()->create([
        'grupo' => 'Civil',
        'numero_as' => '98.3',
        'escopo' => 'Escopo enviado',
    ]);
    $autorizacaoServico = AutorizacaoServico::create([
        'obra_id' => $obra->id,
        'as_escopo_id' => $escopo->id,
        'status' => AsStatus::ENVIADA,
        'numero_as' => 'AS-ENVIADA-SYNC',
        'valor' => 100,
        'valor_estimado' => 100,
    ]);
    $linha = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $escopo->id,
        'numero_complemento' => null,
        'valor_estimado_as' => 100,
        'percentual_total' => 100,
    ]);
    $autorizacaoServico->forceFill(['controle_nota_fiscal_item_id' => $linha->id])->save();
    $simulacao = CapexSimulacao::create([
        'projeto_id' => $obra->projeto_id,
        'nome' => 'Simulacao OI AS Enviada',
        'area_unidade' => 100,
        'fator_correcao' => 1,
        'status' => 1,
    ]);
    CapexSimulacaoItem::create([
        'capex_simulacao_id' => $simulacao->id,
        'as_escopo_id' => $escopo->id,
        'numero_complemento' => '',
        'tipo' => 'auto',
        'incluir' => true,
        'nome_escopo' => 'Escopo enviado',
        'valor_base_m2' => 999.99,
        'area' => 100,
        'fator_correcao' => 1,
        'custo_estimado' => 999.99,
        'percentual' => 100,
    ]);

    $resultado = app(SincronizarSimuladorOiControleAsService::class)
        ->sincronizar($obra, $simulacao);

    expect($resultado['preenchidos'])->toBe(0)
        ->and(implode(' ', $resultado['conflitos']))->toContain('Linha vinculada a AS enviada ignorada')
        ->and($linha->refresh()->valor_estimado_as)->toBe('100.00')
        ->and($autorizacaoServico->refresh()->valor_estimado)->toBe('100.00');
});

it('sincroniza escopo personalizado da simulacao oi vinculada ao projeto e cria linha para gerar as', function () {
    $obra = ObrasFactory::new()->create([
        'unidade' => 'Unidade OI Manual',
    ]);
    $controle = controleNotaFiscalExpansaoParaSincronizacao($obra, [
        'status' => ControleNotaFiscal::STATUS_RASCUNHO,
        'data_base' => now()->toDateString(),
        'unidade' => $obra->unidade,
    ]);
    $escopo = AsEscopoFactory::new()->create([
        'grupo' => 'Civil',
        'numero_as' => '77.1',
        'escopo' => 'Escopo vinculado por unidade',
        'is_personalizado' => true,
        'percentual_faturamento_mao_obra_default' => 68,
        'percentual_faturamento_material_default' => 32,
    ]);
    $simulacao = CapexSimulacao::create([
        'projeto_id' => $obra->projeto_id,
        'nome' => 'Unidade OI Manual',
        'sigla' => 'SIM-OI',
        'area_unidade' => 100,
        'fator_correcao' => 1,
        'status' => 1,
    ]);
    $itemSimulador = CapexSimulacaoItem::create([
        'capex_simulacao_id' => $simulacao->id,
        'as_escopo_id' => $escopo->id,
        'numero_complemento' => '',
        'tipo' => 'manual',
        'incluir' => true,
        'nome_escopo' => 'Escopo vinculado por unidade',
        'valor_base_m2' => 1234.56,
        'area' => null,
        'fator_correcao' => 1,
        'custo_estimado' => 1234.56,
        'percentual' => 100,
    ]);

    $resultado = app(SincronizarSimuladorOiControleAsService::class)
        ->sincronizar($obra, $simulacao);

    $linhaCriada = $controle->itens()->sole();

    expect($resultado['criados'])->toBe(1)
        ->and($linhaCriada->as_escopo_id)->toBe($escopo->id)
        ->and($linhaCriada->asEscopo?->is_personalizado)->toBeTrue()
        ->and($linhaCriada->capex_simulacao_item_id)->toBe($itemSimulador->id)
        ->and($linhaCriada->valor_estimado_as)->toBe('1234.56')
        ->and($linhaCriada->percentual_faturamento_mao_obra)->toBe('68.00')
        ->and($linhaCriada->percentual_faturamento_material)->toBe('32.00');
});

it('importa item manual do simulador oi sem escopo vinculado', function () {
    $obra = ObrasFactory::new()->create([
        'unidade' => 'Unidade OI Sem Escopo',
    ]);
    $controle = controleNotaFiscalExpansaoParaSincronizacao($obra, [
        'status' => ControleNotaFiscal::STATUS_RASCUNHO,
        'data_base' => now()->toDateString(),
        'unidade' => $obra->unidade,
    ]);
    $simulacao = CapexSimulacao::create([
        'projeto_id' => null,
        'nome' => 'Unidade OI Sem Escopo',
        'sigla' => 'OI-MANUAL',
        'area_unidade' => 100,
        'fator_correcao' => 1,
        'status' => 1,
    ]);
    $itemSimulador = CapexSimulacaoItem::create([
        'capex_simulacao_id' => $simulacao->id,
        'as_escopo_id' => null,
        'numero_complemento' => '',
        'tipo' => 'manual',
        'incluir' => true,
        'nome_escopo' => 'Item manual criado apenas na OI',
        'valor_base_m2' => 456.78,
        'area' => null,
        'fator_correcao' => 1,
        'custo_estimado' => 456.78,
        'percentual' => 100,
    ]);

    $resultado = app(SincronizarSimuladorOiControleAsService::class)
        ->sincronizar($obra, $simulacao);

    $linhaCriada = $controle->itens()->sole();

    expect($resultado['criados'])->toBe(1)
        ->and($resultado['preenchidos'])->toBe(1)
        ->and($linhaCriada->as_escopo_id)->not->toBeNull()
        ->and($linhaCriada->asEscopo?->is_personalizado)->toBeTrue()
        ->and($linhaCriada->asEscopo?->controle_nota_fiscal_id)->toBe($controle->id)
        ->and($linhaCriada->asEscopo?->capex_simulacao_item_id)->toBe($itemSimulador->id)
        ->and($linhaCriada->asEscopo?->numero_as)->toBe("OI-{$controle->id}-{$itemSimulador->id}")
        ->and($linhaCriada->escopo)->toBe('Item manual criado apenas na OI')
        ->and($linhaCriada->capex_simulacao_item_id)->toBe($itemSimulador->id)
        ->and($linhaCriada->valor_estimado_as)->toBe('456.78');

    expect(AsEscopo::query()
        ->globais()
        ->where('escopo', 'Item manual criado apenas na OI')
        ->exists())->toBeFalse();

    $resultadoReprocessado = app(SincronizarSimuladorOiControleAsService::class)
        ->sincronizar($obra, $simulacao);

    expect($resultadoReprocessado['criados'])->toBe(0)
        ->and($controle->itens()->count())->toBe(1);
});

it('nao localiza simulacao oi por unidade quando a obra nao tem projeto vinculado', function () {
    $obra = ObrasFactory::new()->create([
        'unidade' => 'Unidade Busca OI',
    ]);
    $simulacao = CapexSimulacao::create([
        'projeto_id' => null,
        'nome' => 'Unidade Busca OI',
        'area_unidade' => 100,
        'fator_correcao' => 1,
        'status' => 1,
    ]);

    $encontrada = app(SincronizarSimuladorOiControleAsService::class)
        ->encontrarAprovadaParaObra($obra);

    expect($encontrada)->toBeNull();
});

it('preenche valor estimado do simulador oi somente na linha de escopo informada', function () {
    $obra = ObrasFactory::new()->create();
    $controle = controleNotaFiscalExpansaoParaSincronizacao($obra, [
        'status' => ControleNotaFiscal::STATUS_ATIVO,
        'data_base' => now()->toDateString(),
        'unidade' => $obra->unidade,
    ]);
    $escopoShell = AsEscopoFactory::new()->create([
        'grupo' => 'Shell',
        'numero_as' => '01.9',
        'escopo' => 'Shell linha',
    ]);
    $escopoCivil = AsEscopoFactory::new()->create([
        'grupo' => 'Civil',
        'numero_as' => '02.9',
        'escopo' => 'Civil linha',
    ]);
    $linhaShell = $controle->itens()->create([
        'as_escopo_id' => $escopoShell->id,
        'grupo' => $escopoShell->grupo,
        'numero_as' => $escopoShell->numero_as,
        'escopo' => $escopoShell->escopo,
        'valor_estimado_as' => 0,
    ]);
    $linhaCivil = $controle->itens()->create([
        'as_escopo_id' => $escopoCivil->id,
        'grupo' => $escopoCivil->grupo,
        'numero_as' => $escopoCivil->numero_as,
        'escopo' => $escopoCivil->escopo,
        'valor_estimado_as' => 0,
    ]);
    $simulacao = CapexSimulacao::create([
        'projeto_id' => $obra->projeto_id,
        'nome' => 'Simulacao OI Linha',
        'area_unidade' => 100,
        'fator_correcao' => 1,
        'status' => 1,
    ]);
    $itemSimulador = CapexSimulacaoItem::create([
        'capex_simulacao_id' => $simulacao->id,
        'as_escopo_id' => $escopoShell->id,
        'numero_complemento' => '',
        'tipo' => 'manual',
        'incluir' => true,
        'nome_escopo' => 'Shell linha',
        'valor_base_m2' => 777,
        'area' => null,
        'fator_correcao' => 1,
        'custo_estimado' => 777,
        'percentual' => 100,
    ]);

    $resultado = app(SincronizarSimuladorOiControleAsService::class)
        ->sincronizarItem($linhaShell, $simulacao);

    expect($resultado['preenchidos'])->toBe(1)
        ->and($linhaShell->refresh()->valor_estimado_as)->toBe('777.00')
        ->and($linhaShell->capex_simulacao_item_id)->toBe($itemSimulador->id)
        ->and($linhaCivil->refresh()->valor_estimado_as)->toBe('0.00');
});

function controleNotaFiscalExpansaoParaSincronizacao(Obras $obra, array $attributes = []): ControleNotaFiscal
{
    $controle = ControleNotaFiscal::query()
        ->where('obra_id', $obra->id)
        ->where('tipo_unidade', TipoUnidade::EXPANSAO->value)
        ->firstOrFail();

    $controle->itens()->delete();

    $controle->forceFill($attributes)->save();

    return $controle->refresh();
}
