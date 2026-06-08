<?php

use App\Enums\TipoUnidade;
use App\Models\ControleNotaFiscal;
use App\Models\ControleNotaFiscalAuxiliar;
use App\Models\Obras;
use Database\Factories\AsaFactory;
use Database\Factories\AutorizacaoServicoFactory;
use Database\Factories\ControleNotaFiscalFactory;
use Database\Factories\ControleNotaFiscalItemFactory;
use Database\Factories\ControleNotaFiscalNotaFactory;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

it('permite um controle de nota fiscal por tipo da obra', function () {
    $obra = Obras::factory()->create();

    ControleNotaFiscalFactory::new()->create([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::RETROFIT->value,
    ]);

    expect(ControleNotaFiscal::query()->where('obra_id', $obra->id)->count())->toBe(2);
});

it('nao permite controle de nota fiscal duplicado para o mesmo tipo da obra', function () {
    $obra = Obras::factory()->create();

    expect(fn () => ControleNotaFiscalFactory::new()->create([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ]))->toThrow(QueryException::class);
});

it('remove o controle e seus registros filhos ao excluir definitivamente a obra', function () {
    $obra = Obras::factory()->create();

    $controle = ControleNotaFiscal::query()
        ->where('obra_id', $obra->id)
        ->where('tipo_unidade', TipoUnidade::EXPANSAO->value)
        ->firstOrFail();

    $item = ControleNotaFiscalItemFactory::new()->create([
        'controle_nota_fiscal_id' => $controle->id,
    ]);

    $auxiliar = ControleNotaFiscalAuxiliar::query()->create([
        'controle_nota_fiscal_id' => $controle->id,
        'grupo' => 'Projeto',
        'escopo' => 'Projeto',
        'empresa' => 'Empresa Teste',
        'percentual_total' => 100,
        'percentual_faturamento_mao_obra' => 60,
        'percentual_faturamento_material' => 40,
        'valor_global_a' => 500,
        'total_medicao_a_menos_b' => 500,
        'valor_acumulado_medido' => 0,
        'saldo' => 500,
        'sort_order' => 0,
    ]);

    $obra->forceDelete();

    $this->assertDatabaseMissing('controle_nota_fiscals', ['id' => $controle->id]);
    $this->assertDatabaseMissing('controle_nota_fiscal_items', ['id' => $item->id]);
    $this->assertDatabaseMissing('controle_nota_fiscal_auxiliares', ['id' => $auxiliar->id]);
});

it('remove itens e auxiliares preservando notas ao excluir diretamente o controle', function () {
    $controle = ControleNotaFiscalFactory::new()->create([
        'tipo_unidade' => TipoUnidade::RETROFIT->value,
    ]);

    $item = ControleNotaFiscalItemFactory::new()->create([
        'controle_nota_fiscal_id' => $controle->id,
    ]);

    $auxiliar = ControleNotaFiscalAuxiliar::query()->create([
        'controle_nota_fiscal_id' => $controle->id,
        'grupo' => 'Projeto',
        'escopo' => 'Projeto',
        'empresa' => 'Empresa Teste',
        'percentual_total' => 100,
        'percentual_faturamento_mao_obra' => 60,
        'percentual_faturamento_material' => 40,
        'valor_global_a' => 500,
        'total_medicao_a_menos_b' => 500,
        'valor_acumulado_medido' => 0,
        'saldo' => 500,
        'sort_order' => 0,
    ]);

    $autorizacaoServico = AutorizacaoServicoFactory::new()->create([
        'obra_id' => $controle->obra_id,
        'controle_nota_fiscal_item_id' => $item->id,
    ]);

    $asa = AsaFactory::new()->create([
        'controle_nota_fiscal_auxiliar_id' => $auxiliar->id,
    ]);

    $notaItem = ControleNotaFiscalNotaFactory::new()->create([
        'autorizacao_servico_id' => $autorizacaoServico->id,
        'autorizacao_servico_adicional_id' => null,
    ]);

    $notaAuxiliar = ControleNotaFiscalNotaFactory::new()->create([
        'autorizacao_servico_id' => null,
        'autorizacao_servico_adicional_id' => $asa->id,
    ]);

    $controle->delete();

    $this->assertDatabaseMissing('controle_nota_fiscal_items', ['id' => $item->id]);
    $this->assertDatabaseMissing('controle_nota_fiscal_auxiliares', ['id' => $auxiliar->id]);
    $this->assertDatabaseHas('controle_nota_fiscal_notas', ['id' => $notaItem->id]);
    $this->assertDatabaseHas('controle_nota_fiscal_notas', ['id' => $notaAuxiliar->id]);
    $this->assertDatabaseHas('autorizacao_servicos', [
        'id' => $autorizacaoServico->id,
        'controle_nota_fiscal_item_id' => null,
    ]);
    $this->assertDatabaseHas('autorizacao_servico_adicionais', [
        'id' => $asa->id,
        'controle_nota_fiscal_auxiliar_id' => null,
    ]);
});
