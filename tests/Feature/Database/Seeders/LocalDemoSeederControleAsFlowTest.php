<?php

use App\Enums\AsStatus;
use App\Enums\StatusControleNotaFiscalNota;
use App\Models\AutorizacaoServico;
use App\Models\CapexSimulacao;
use App\Models\Construtora;
use App\Models\ControleNotaFiscal;
use App\Models\ControleNotaFiscalItem;
use App\Models\ControleNotaFiscalNota;
use App\Models\Obras;
use App\Models\Projeto;
use App\Models\User;
use Database\Seeders\AsEscopoSeeder;
use Database\Seeders\LocalDemoSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;

uses(DatabaseTransactions::class);

it('semeia o fluxo demo de oi ate aprovacao de nota fiscal com relacionamentos consistentes', function () {
    config(['filesystems.media_disk' => 'r2']);
    Storage::fake('r2');

    $construtora = Construtora::create([
        'nome' => 'Fornecedor Demo Local',
        'cnpj' => '12.345.678/0001-99',
        'email' => 'contato@construtora-demo.test',
        'tipo' => 'CONSTRUTORA',
    ]);
    User::factory()->active()->create(['email' => 'coordenador.orcamentos@example.test']);
    User::factory()->active()->create(['email' => 'gestor.obra@example.test']);

    $projeto = Projeto::factory()->create([
        'nome' => 'Projeto Demo Fluxo AS',
        'sigla' => 'DEMO-PJT-001',
        'endereco' => 'Rua Demo Fluxo, 100',
    ]);
    $obra = Obras::factory()->create([
        'projeto_id' => $projeto->id,
        'codigo' => 'DEMO-FLUXO-AS',
        'unidade' => 'Unidade Demo Fluxo AS',
        'endereco' => 'Rua Demo Fluxo, 100',
        'inicio' => now()->toDateString(),
        'fim' => now()->addMonth()->toDateString(),
    ]);
    ControleNotaFiscal::create([
        'obra_id' => $obra->id,
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'unidade' => $obra->unidade,
        'sigla' => $projeto->sigla,
        'endereco' => $obra->endereco,
    ]);

    $this->seed(AsEscopoSeeder::class);

    $seeder = app(LocalDemoSeeder::class);

    foreach (['seedCapexSimulacoesDemo', 'seedControleAsFluxoOiDemo'] as $methodName) {
        $method = new ReflectionMethod($seeder, $methodName);
        $method->setAccessible(true);
        $method->invoke($seeder);
    }

    $simulacao = CapexSimulacao::query()
        ->where('sigla', 'DEMO-CAPEX-001')
        ->with('itens')
        ->sole();
    $itensSimulador = $simulacao->itens;

    expect($itensSimulador)->toHaveCount(3)
        ->and($itensSimulador->pluck('as_escopo_id')->filter()->unique()->count())->toBeGreaterThanOrEqual(2)
        ->and($itensSimulador->pluck('numero_complemento')->contains('C1'))->toBeTrue();

    $itensControle = ControleNotaFiscalItem::query()
        ->whereHas('controleNotaFiscal', fn ($query) => $query->where('obra_id', $obra->id))
        ->with(['autorizacaoServico', 'notas'])
        ->orderBy('id')
        ->get();

    expect($itensControle)->toHaveCount(3);

    foreach ($itensControle as $item) {
        expect($item->empresa)->toBe($construtora->nome)
            ->and($item->capex_simulacao_item_id)->not->toBeNull()
            ->and($item->valor_estimado_as_simulador)->not->toBeNull()
            ->and($item->autorizacaoServico)->toBeInstanceOf(AutorizacaoServico::class)
            ->and($item->autorizacaoServico->construtora_id)->toBe($construtora->id)
            ->and($item->autorizacaoServico->status)->toBe(AsStatus::ENVIADA)
            ->and($item->liberado_para_fornecedor_at)->not->toBeNull();
    }

    $itemComplementar = $itensControle->firstWhere('numero_complemento', 'C1');
    $autorizacaoServicoIds = $itensControle
        ->map(fn (ControleNotaFiscalItem $item): ?int => $item->autorizacaoServico?->id)
        ->filter()
        ->values();

    expect($itemComplementar)->not->toBeNull()
        ->and($itemComplementar->capex_simulacao_item_id)->toBe(
            $itensSimulador->firstWhere('numero_complemento', 'C1')->id,
        );

    expect(ControleNotaFiscalNota::query()
        ->whereIn('autorizacao_servico_id', $autorizacaoServicoIds)
        ->where('status', StatusControleNotaFiscalNota::EM_ANALISE->value)
        ->exists())->toBeTrue()
        ->and(ControleNotaFiscalNota::query()
            ->whereIn('autorizacao_servico_id', $autorizacaoServicoIds)
            ->where('status', StatusControleNotaFiscalNota::APROVADO->value)
            ->exists())->toBeTrue();
});
