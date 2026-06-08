<?php

use App\Enums\TipoUnidade;
use App\Models\ControleNotaFiscal;
use App\Models\ControleNotaFiscalAuxiliar;
use App\Models\ControleNotaFiscalNota;
use App\Models\ElaboracaoAditivo;
use App\Models\Obras;
use App\Services\AsaService;
use Database\Factories\AsaFactory;
use Database\Factories\ConstrutoraFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(LazilyRefreshDatabase::class);

it('creates auxiliar item when ASA receives budget approval', function (): void {
    [$asa, $controle, $construtora] = asaAprovadaParaSincronizacaoFiscal();

    $auxiliar = app(AsaService::class)->sincronizarItemAuxiliarFiscal($asa);

    expect($auxiliar)->toBeInstanceOf(ControleNotaFiscalAuxiliar::class)
        ->and($auxiliar->controle_nota_fiscal_id)->toBe($controle->id)
        ->and($auxiliar->numero_as)->toBe($asa->numero_asa)
        ->and($auxiliar->numero_complemento)->toBe('')
        ->and($auxiliar->empresa)->toBe($construtora->nome)
        ->and((float) $auxiliar->valor_global_a)->toBe(1234.56)
        ->and($asa->refresh()->controle_nota_fiscal_auxiliar_id)->toBe($auxiliar->id);
});

it('stores ASA labor and material percentages from aditivo items in auxiliary row', function (): void {
    [$asa] = asaAprovadaParaSincronizacaoFiscal();

    $auxiliar = app(AsaService::class)->sincronizarItemAuxiliarFiscal($asa);

    expect($auxiliar->percentual_faturamento_mao_obra)->toBe('65.00')
        ->and($auxiliar->percentual_faturamento_material)->toBe('35.00');
});

it('uses aditivo construtora as auxiliary company instead of aditivo author', function (): void {
    [$asa, , $construtora] = asaAprovadaParaSincronizacaoFiscal();

    $asa->forceFill(['solicitante' => 'Autor do aditivo'])->save();

    $auxiliar = app(AsaService::class)->sincronizarItemAuxiliarFiscal($asa->refresh());

    expect($auxiliar->empresa)->toBe($construtora->nome);
});

it('does not create duplicate auxiliar item when approval is repeated', function (): void {
    [$asa] = asaAprovadaParaSincronizacaoFiscal();

    $primeiroAuxiliar = app(AsaService::class)->sincronizarItemAuxiliarFiscal($asa);
    $segundoAuxiliar = app(AsaService::class)->sincronizarItemAuxiliarFiscal($asa->refresh());

    expect($segundoAuxiliar->id)->toBe($primeiroAuxiliar->id)
        ->and(ControleNotaFiscalAuxiliar::query()->where('numero_as', $asa->numero_asa)->count())->toBe(1);
});

it('does not mutate fiscal sensitive fields after ASA has notes', function (): void {
    [$asa] = asaAprovadaParaSincronizacaoFiscal();
    $auxiliar = app(AsaService::class)->sincronizarItemAuxiliarFiscal($asa);

    ControleNotaFiscalNota::query()->create([
        'autorizacao_servico_adicional_id' => $asa->id,
        'tipo_medicao' => ControleNotaFiscalNota::TIPO_MEDICAO_MAO_OBRA,
        'empresa' => $auxiliar->empresa,
        'cnpj_fornecedor' => '12345678000195',
        'numero_nf' => '123',
        'cnpj_faturamento' => '12345678000195',
        'valor_acumulado_medido_nf' => 100,
        'status' => 'pendente',
        'sort_order' => 1,
    ]);

    $asa->forceFill([
        'objeto' => 'Escopo alterado indevidamente',
        'valor_total' => 9999,
        'solicitante' => 'Outro fornecedor',
    ])->save();

    app(AsaService::class)->sincronizarItemAuxiliarFiscal($asa->refresh());

    expect($auxiliar->refresh()->escopo)->not->toBe('Escopo alterado indevidamente')
        ->and((float) $auxiliar->valor_global_a)->toBe(1234.56)
        ->and($auxiliar->empresa)->not->toBe('Outro fornecedor');
});

it('blocks ASA auxiliary sync when controle is encerrado', function (): void {
    [$asa] = asaAprovadaParaSincronizacaoFiscal(ControleNotaFiscal::STATUS_ENCERRADO);

    expect(fn () => app(AsaService::class)->sincronizarItemAuxiliarFiscal($asa))
        ->toThrow(ValidationException::class);
});

function asaAprovadaParaSincronizacaoFiscal(string $controleStatus = ControleNotaFiscal::STATUS_ATIVO): array
{
    $obra = Obras::factory()->create();
    $controle = ControleNotaFiscal::query()
        ->where('obra_id', $obra->id)
        ->where('tipo_unidade', TipoUnidade::EXPANSAO->value)
        ->firstOrFail();
    $controle->forceFill(['status' => $controleStatus])->save();

    $user = UserFactory::new()->active()->create();
    $construtora = ConstrutoraFactory::new()->create(['nome' => 'Fornecedor ASA']);
    $aditivo = ElaboracaoAditivo::query()->create([
        'user_id' => $user->id,
        'construtora_id' => $construtora->id,
        'data' => now(),
        'justificativa' => 'Aditivo aprovado para teste',
        'obra_id' => $obra->id,
        'status_fluxo' => 'aprovado',
        'aprovado_gestor_por_id' => $user->id,
        'aprovado_gestor_em' => now(),
        'aprovado_orcamento_por_id' => $user->id,
        'aprovado_orcamento_em' => now(),
    ]);
    $aditivo->itens()->create([
        'item' => '1',
        'descricao_servico' => 'Mao de obra e material ASA',
        'quantidade' => 5,
        'unidade' => 'un',
        'valor_material_unitario' => 70,
        'valor_mao_obra_unitario' => 130,
        'total_unitario' => 200,
        'valor_total_geral' => 1000,
    ]);

    $asa = AsaFactory::new()->create([
        'projeto_id' => $obra->projeto_id,
        'elaboracao_aditivo_id' => $aditivo->id,
        'numero_asa' => 'ASA-FISCAL-'.str()->upper(str()->random(6)),
        'status' => 'aprovado',
        'objeto' => 'Escopo adicional aprovado',
        'solicitante' => $construtora->nome,
        'valor_total' => 1234.56,
    ]);

    return [$asa, $controle->refresh(), $construtora];
}
