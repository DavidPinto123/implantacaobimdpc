<?php

use App\Enums\TipoUnidade;
use App\Models\ControleNotaFiscal;
use App\Models\ControleNotaFiscalItem;
use App\Services\ControleAutorizacaoServicoItemService;
use Database\Factories\AsEscopoFactory;
use Database\Factories\ObrasFactory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

uses(TestCase::class, DatabaseTransactions::class);

it('marca valor estimado como editado manualmente quando diverge do simulador oi', function () {
    $obra = ObrasFactory::new()->create();
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'unidade' => $obra->unidade,
    ]);
    $escopo = AsEscopoFactory::new()->create();
    $item = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $escopo->id,
        'valor_estimado_as' => 1000,
        'valor_estimado_as_simulador' => 1000,
        'valor_estimado_as_editado_manualmente' => false,
        'percentual_total' => 100,
    ]);

    app(ControleAutorizacaoServicoItemService::class)->persistir($item, [
        'as_escopo_id' => $escopo->id,
        'valor_estimado' => '1.200,00',
        'valor_fechado' => '1.200,00',
    ]);

    expect($item->refresh()->valor_estimado_as)->toBe('1200.00')
        ->and($item->valor_estimado_as_simulador)->toBe('1000.00')
        ->and($item->valor_estimado_as_editado_manualmente)->toBeTrue();
});

it('remove marcacao manual quando valor estimado volta ao valor do simulador oi', function () {
    $obra = ObrasFactory::new()->create();
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'unidade' => $obra->unidade,
    ]);
    $escopo = AsEscopoFactory::new()->create();
    $item = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $escopo->id,
        'valor_estimado_as' => 1200,
        'valor_estimado_as_simulador' => 1000,
        'valor_estimado_as_editado_manualmente' => true,
        'percentual_total' => 100,
    ]);

    app(ControleAutorizacaoServicoItemService::class)->persistir($item, [
        'as_escopo_id' => $escopo->id,
        'valor_estimado' => '1.000,00',
        'valor_fechado' => '1.200,00',
    ]);

    expect($item->refresh()->valor_estimado_as)->toBe('1000.00')
        ->and($item->valor_estimado_as_editado_manualmente)->toBeFalse();
});

it('aplica percentuais padrao do escopo ao selecionar escopo em linha sem percentuais manuais', function () {
    $obra = ObrasFactory::new()->create();
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'unidade' => $obra->unidade,
    ]);
    $escopo = AsEscopoFactory::new()->create([
        'percentual_faturamento_mao_obra_default' => 65,
        'percentual_faturamento_material_default' => 35,
    ]);
    $item = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'percentual_total' => 100,
    ]);

    app(ControleAutorizacaoServicoItemService::class)->persistir($item, [
        'as_escopo_id' => $escopo->id,
        'valor_estimado' => '1.000,00',
        'valor_fechado' => '1.000,00',
    ]);

    expect($item->refresh()->as_escopo_id)->toBe($escopo->id)
        ->and($item->percentual_faturamento_mao_obra)->toBe('65.00')
        ->and($item->percentual_faturamento_material)->toBe('35.00');
});
