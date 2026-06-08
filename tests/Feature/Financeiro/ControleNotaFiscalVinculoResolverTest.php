<?php

use App\Enums\AsStatus;
use App\Enums\ModoSaldoFiscal;
use App\Enums\TipoDestinoFiscal;
use App\Enums\TipoUnidade;
use App\Models\ControleNotaFiscal;
use App\Models\Obras;
use App\Services\ControleNotaFiscal\ControleNotaFiscalVinculoResolver;
use Database\Factories\AsaFactory;
use Database\Factories\AutorizacaoServicoFactory;
use Database\Factories\ConstrutoraFactory;
use Database\Factories\ControleNotaFiscalAuxiliarFactory;
use Database\Factories\ControleNotaFiscalItemFactory;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('resolves AS only inside controle obra and tipo unidade', function (): void {
    [$obra, $controle] = obraComControleExpansao();
    $construtora = ConstrutoraFactory::new()->create(['nome' => 'Fornecedor Alpha']);
    $item = ControleNotaFiscalItemFactory::new()->create([
        'controle_nota_fiscal_id' => $controle->id,
        'empresa' => $construtora->nome,
        'valor_global_a' => 1000,
        'total_medicao_a_menos_b' => 1000,
        'saldo' => 1000,
        'liberado_para_fornecedor_at' => now(),
    ]);
    $as = AutorizacaoServicoFactory::new()->create([
        'obra_id' => $obra->id,
        'controle_nota_fiscal_item_id' => $item->id,
        'construtora_id' => $construtora->id,
        'status' => AsStatus::ENVIADA,
        'valor' => 1000,
    ]);

    $destino = app(ControleNotaFiscalVinculoResolver::class)->resolveAs(
        obraId: $obra->id,
        tipoUnidade: TipoUnidade::EXPANSAO->value,
        autorizacaoServicoId: $as->id,
        construtoraId: $construtora->id,
        modoSaldo: ModoSaldoFiscal::Comprometido,
    );

    expect($destino->bloqueado())->toBeFalse()
        ->and($destino->tipo)->toBe(TipoDestinoFiscal::AS)
        ->and($destino->controle->is($controle))->toBeTrue()
        ->and($destino->documento()->is($as))->toBeTrue()
        ->and($destino->item()->is($item))->toBeTrue()
        ->and($destino->saldoDisponivel)->toBe(1000.0);
});

it('blocks AS when controle is encerrado', function (): void {
    [$obra, $controle] = obraComControleExpansao();
    $item = ControleNotaFiscalItemFactory::new()->create([
        'controle_nota_fiscal_id' => $controle->id,
        'liberado_para_fornecedor_at' => now(),
    ]);
    $as = AutorizacaoServicoFactory::new()->create([
        'obra_id' => $obra->id,
        'controle_nota_fiscal_item_id' => $item->id,
        'status' => AsStatus::ENVIADA,
    ]);
    $controle->forceFill(['status' => ControleNotaFiscal::STATUS_ENCERRADO])->save();

    $destino = app(ControleNotaFiscalVinculoResolver::class)->resolveAs(
        obraId: $obra->id,
        tipoUnidade: TipoUnidade::EXPANSAO->value,
        autorizacaoServicoId: $as->id,
        construtoraId: null,
        modoSaldo: ModoSaldoFiscal::Comprometido,
    );

    expect($destino->bloqueado())->toBeTrue()
        ->and($destino->motivoBloqueio)->toBe('controle_encerrado');
});

it('blocks fornecedor divergente for AS', function (): void {
    [$obra, $controle] = obraComControleExpansao();
    $construtora = ConstrutoraFactory::new()->create();
    $outraConstrutora = ConstrutoraFactory::new()->create();
    $item = ControleNotaFiscalItemFactory::new()->create([
        'controle_nota_fiscal_id' => $controle->id,
        'empresa' => $construtora->nome,
        'liberado_para_fornecedor_at' => now(),
    ]);
    $as = AutorizacaoServicoFactory::new()->create([
        'obra_id' => $obra->id,
        'controle_nota_fiscal_item_id' => $item->id,
        'construtora_id' => $construtora->id,
        'status' => AsStatus::ENVIADA,
    ]);

    $destino = app(ControleNotaFiscalVinculoResolver::class)->resolveAs(
        obraId: $obra->id,
        tipoUnidade: TipoUnidade::EXPANSAO->value,
        autorizacaoServicoId: $as->id,
        construtoraId: $outraConstrutora->id,
        modoSaldo: ModoSaldoFiscal::Comprometido,
    );

    expect($destino->bloqueado())->toBeTrue()
        ->and($destino->motivoBloqueio)->toBe('fornecedor_divergente');
});

it('blocks AS without direct controle item link', function (): void {
    [$obra, $controle] = obraComControleExpansao();
    ControleNotaFiscalItemFactory::new()->create([
        'controle_nota_fiscal_id' => $controle->id,
        'liberado_para_fornecedor_at' => now(),
    ]);
    $as = AutorizacaoServicoFactory::new()->create([
        'obra_id' => $obra->id,
        'controle_nota_fiscal_item_id' => null,
        'status' => AsStatus::ENVIADA,
    ]);

    $destino = app(ControleNotaFiscalVinculoResolver::class)->resolveAs(
        obraId: $obra->id,
        tipoUnidade: TipoUnidade::EXPANSAO->value,
        autorizacaoServicoId: $as->id,
        construtoraId: null,
        modoSaldo: ModoSaldoFiscal::Comprometido,
    );

    expect($destino->bloqueado())->toBeTrue()
        ->and($destino->motivoBloqueio)->toBe('destino_nao_encontrado');
});

it('resolves ASA through controle auxiliar', function (): void {
    [$obra, $controle] = obraComControleExpansao();
    $construtora = ConstrutoraFactory::new()->create(['nome' => 'Fornecedor Beta']);
    $auxiliar = ControleNotaFiscalAuxiliarFactory::new()->create([
        'controle_nota_fiscal_id' => $controle->id,
        'empresa' => $construtora->nome,
        'valor_global_a' => 500,
        'total_medicao_a_menos_b' => 500,
        'saldo' => 500,
        'liberado_para_fornecedor_at' => now(),
    ]);
    $asa = AsaFactory::new()->create([
        'projeto_id' => $obra->projeto_id,
        'controle_nota_fiscal_auxiliar_id' => $auxiliar->id,
        'status' => 'aprovado',
        'solicitante' => $construtora->nome,
        'valor_total' => 500,
    ]);

    $destino = app(ControleNotaFiscalVinculoResolver::class)->resolveAsa(
        obraId: $obra->id,
        tipoUnidade: TipoUnidade::EXPANSAO->value,
        asaId: $asa->id,
        construtoraId: $construtora->id,
        modoSaldo: ModoSaldoFiscal::Comprometido,
    );

    expect($destino->bloqueado())->toBeFalse()
        ->and($destino->tipo)->toBe(TipoDestinoFiscal::ASA)
        ->and($destino->controle->is($controle))->toBeTrue()
        ->and($destino->documento()->is($asa))->toBeTrue()
        ->and($destino->item()->is($auxiliar))->toBeTrue()
        ->and($destino->saldoDisponivel)->toBe(500.0);
});

function obraComControleExpansao(array $controleAttributes = []): array
{
    $obra = Obras::factory()->create();

    $controle = ControleNotaFiscal::query()
        ->where('obra_id', $obra->id)
        ->where('tipo_unidade', TipoUnidade::EXPANSAO->value)
        ->firstOrFail();

    if ($controleAttributes !== []) {
        $controle->forceFill($controleAttributes)->save();
    }

    return [$obra, $controle->refresh()];
}
