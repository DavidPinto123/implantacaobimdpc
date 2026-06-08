<?php

use App\Enums\TipoUnidade;
use App\Models\AutorizacaoServico;
use Database\Factories\AsEscopoFactory;
use Database\Factories\ConstrutoraFactory;
use Database\Factories\ControleNotaFiscalFactory;
use Database\Factories\ControleNotaFiscalItemFactory;
use Database\Factories\ObrasFactory;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

it('sincroniza o fornecedor na autorização de serviço existente quando a empresa da linha é preenchida', function () {
    $escopo = AsEscopoFactory::new()->create([
        'numero_as' => 'AS-7777',
    ]);

    $construtora = ConstrutoraFactory::new()->create();

    $controle = ControleNotaFiscalFactory::new()->create([
        'obra_id' => ObrasFactory::new(),
        'construtora_id' => $construtora->id,
        'tipo_unidade' => TipoUnidade::RETROFIT->value,
    ]);

    $autorizacao = AutorizacaoServico::create([
        'obra_id' => $controle->obra_id,
        'as_escopo_id' => $escopo->id,
        'construtora_id' => null,
        'numero_as' => 'DEMO-SF-EXP-7777',
        'numero_complemento' => null,
        'valor' => 0,
        'observacoes' => null,
    ]);

    ControleNotaFiscalItemFactory::new()->create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $escopo->id,
        'empresa' => $construtora->nome,
        'liberado_para_fornecedor_at' => now(),
    ]);

    expect($autorizacao->fresh()?->construtora_id)->toBe($construtora->id);
});

it('nao sincroniza autorizacao existente quando linha em rascunho ainda nao esta vinculada', function () {
    $escopo = AsEscopoFactory::new()->create([
        'numero_as' => 'AS-8888',
    ]);

    $construtora = ConstrutoraFactory::new()->create();

    $controle = ControleNotaFiscalFactory::new()->create([
        'obra_id' => ObrasFactory::new(),
        'construtora_id' => $construtora->id,
        'tipo_unidade' => TipoUnidade::RETROFIT->value,
    ]);

    $autorizacao = AutorizacaoServico::create([
        'obra_id' => $controle->obra_id,
        'as_escopo_id' => $escopo->id,
        'construtora_id' => null,
        'numero_as' => 'DEMO-SF-EXP-8888',
        'numero_complemento' => null,
        'valor' => 0,
        'observacoes' => null,
    ]);

    ControleNotaFiscalItemFactory::new()->create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $escopo->id,
        'empresa' => $construtora->nome,
        'liberado_para_fornecedor_at' => null,
    ]);

    expect($autorizacao->fresh()?->construtora_id)->toBeNull();
});

it('nao sincroniza autorizacao vinculada a outra obra', function () {
    $escopo = AsEscopoFactory::new()->create([
        'numero_as' => 'AS-7777',
    ]);

    $construtoraOriginal = ConstrutoraFactory::new()->create();
    $construtoraNova = ConstrutoraFactory::new()->create();
    $controle = ControleNotaFiscalFactory::new()->create([
        'obra_id' => ObrasFactory::new(),
        'construtora_id' => $construtoraNova->id,
        'tipo_unidade' => TipoUnidade::RETROFIT->value,
    ]);

    $autorizacao = AutorizacaoServico::create([
        'obra_id' => ObrasFactory::new()->create()->id,
        'as_escopo_id' => $escopo->id,
        'construtora_id' => $construtoraOriginal->id,
        'numero_as' => 'DEMO-SF-EXP-7777',
        'numero_complemento' => null,
        'valor' => 0,
        'observacoes' => null,
    ]);

    ControleNotaFiscalItemFactory::new()->create([
        'controle_nota_fiscal_id' => $controle->id,
        'autorizacao_servico_id' => $autorizacao->id,
        'as_escopo_id' => $escopo->id,
        'empresa' => $construtoraNova->nome,
        'liberado_para_fornecedor_at' => now(),
    ]);

    expect($autorizacao->fresh()?->construtora_id)->toBe($construtoraOriginal->id);
});

it('não cria autorização de serviço automaticamente quando item liberado não possui AS prévia', function () {
    $escopo = AsEscopoFactory::new()->create([
        'numero_as' => 'AS-9100',
    ]);

    $construtora = ConstrutoraFactory::new()->create();

    $controle = ControleNotaFiscalFactory::new()->create([
        'obra_id' => ObrasFactory::new(),
        'construtora_id' => $construtora->id,
        'tipo_unidade' => TipoUnidade::RETROFIT->value,
    ]);

    ControleNotaFiscalItemFactory::new()->create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $escopo->id,
        'empresa' => $construtora->nome,
        'liberado_para_fornecedor_at' => now(),
    ]);

    expect(AutorizacaoServico::query()->count())->toBe(0);
});
