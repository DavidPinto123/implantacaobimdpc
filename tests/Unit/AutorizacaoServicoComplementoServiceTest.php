<?php

use App\Enums\AsStatus;
use App\Models\AutorizacaoServico;
use App\Models\CapexSimulacao;
use App\Models\CapexSimulacaoItem;
use App\Models\ControleNotaFiscal;
use App\Models\ControleNotaFiscalItem;
use App\Services\AutorizacaoServicoComplementoService;
use Database\Factories\AsEscopoFactory;
use Database\Factories\ObrasFactory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

uses(TestCase::class, DatabaseTransactions::class);

it('normaliza complemento vazio como string vazia', function () {
    $service = app(AutorizacaoServicoComplementoService::class);

    expect($service->normalizar(null))->toBe('')
        ->and($service->normalizar(''))->toBe('')
        ->and($service->normalizar(' c1 '))->toBe('C1');
});

it('retorna vazio para primeira linha quando nao existe principal nem complemento', function () {
    $obra = ObrasFactory::new()->create();
    $escopo = AsEscopoFactory::new()->create();
    $service = app(AutorizacaoServicoComplementoService::class);

    expect($service->gerarProximo($obra->id, $escopo->id))->toBe('');
});

it('nao reutiliza a chave principal depois que o escopo ja recebeu sequencia', function () {
    $obra = ObrasFactory::new()->create();
    $escopo = AsEscopoFactory::new()->create();
    $service = app(AutorizacaoServicoComplementoService::class);

    expect($service->gerarProximo($obra->id, $escopo->id))->toBe('')
        ->and($service->gerarProximo($obra->id, $escopo->id))->toBe('C1')
        ->and($service->gerarProximo($obra->id, $escopo->id))->toBe('C2');
});

it('gera proximo complemento pelo maior numero existente mais um', function () {
    $obra = ObrasFactory::new()->create();
    $escopo = AsEscopoFactory::new()->create();
    $controle = ControleNotaFiscal::create([
        'obra_id' => $obra->id,
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'unidade' => $obra->unidade,
    ]);
    $simulacao = CapexSimulacao::create([
        'projeto_id' => $obra->projeto_id,
        'nome' => 'Simulacao Complementos',
        'area_unidade' => 100,
        'fator_correcao' => 1,
        'status' => 1,
    ]);

    ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $escopo->id,
        'numero_complemento' => 'C1',
        'percentual_total' => 100,
    ]);

    AutorizacaoServico::create([
        'obra_id' => $obra->id,
        'as_escopo_id' => $escopo->id,
        'numero_as' => 'AS-COMPLEMENTO-C2',
        'numero_complemento' => 'C2',
        'status' => AsStatus::CRIADA,
        'valor' => 100,
        'valor_estimado' => 100,
    ]);

    CapexSimulacaoItem::create([
        'capex_simulacao_id' => $simulacao->id,
        'as_escopo_id' => $escopo->id,
        'numero_complemento' => 'C3',
        'tipo' => 'auto',
        'incluir' => true,
        'nome_escopo' => 'Complemento C3',
        'valor_base_m2' => 10,
        'area' => 100,
        'fator_correcao' => 1,
        'custo_estimado' => 1000,
        'percentual' => 100,
    ]);

    $service = app(AutorizacaoServicoComplementoService::class);

    expect($service->gerarProximo($obra->id, $escopo->id, capexSimulacaoId: $simulacao->id))->toBe('C4');
});
