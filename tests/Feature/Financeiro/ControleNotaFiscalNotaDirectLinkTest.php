<?php

use App\Models\Asa;
use App\Models\AutorizacaoServico;
use App\Models\ControleNotaFiscalNota;
use Database\Factories\AsaFactory;
use Database\Factories\AutorizacaoServicoFactory;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('persists nota fiscal directly against an AS and derives the controle item', function (): void {
    $autorizacaoServico = AutorizacaoServicoFactory::new()->create();

    $nota = new ControleNotaFiscalNota;
    $nota->forceFill(notaFiscalData([
        'autorizacao_servico_id' => $autorizacaoServico->id,
    ]))->save();

    expect($nota->autorizacaoServico)->toBeInstanceOf(AutorizacaoServico::class)
        ->and($nota->autorizacaoServico->is($autorizacaoServico))->toBeTrue()
        ->and($nota->documentoFaturavel()->is($autorizacaoServico))->toBeTrue();
});

it('persists nota fiscal directly against an ASA and derives the controle auxiliar', function (): void {
    $asa = AsaFactory::new()->create();

    $nota = new ControleNotaFiscalNota;
    $nota->forceFill(notaFiscalData([
        'autorizacao_servico_adicional_id' => $asa->id,
    ]))->save();

    expect($nota->asa)->toBeInstanceOf(Asa::class)
        ->and($nota->asa->is($asa))->toBeTrue()
        ->and($nota->documentoFaturavel()->is($asa))->toBeTrue();
});

function notaFiscalData(array $attributes): array
{
    return [
        'importado_por_id' => null,
        'tipo_medicao' => ControleNotaFiscalNota::TIPO_MEDICAO_MAO_OBRA,
        'empresa' => 'Fornecedor Teste',
        'cnpj_fornecedor' => '12345678000195',
        'numero_nf' => fake()->numerify('NF######'),
        'cnpj_faturamento' => '12345678000195',
        'valor_acumulado_medido_nf' => 100,
        'status' => 'pendente',
        'sort_order' => 1,
        ...$attributes,
    ];
}
