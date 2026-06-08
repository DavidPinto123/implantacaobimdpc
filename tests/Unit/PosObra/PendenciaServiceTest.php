<?php

use App\Enums\PosObra\StatusPendencia;
use App\Services\PosObra\PendenciaService;
use Carbon\Carbon;
use Database\Factories\PendenciaFactory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

uses(TestCase::class, DatabaseTransactions::class);

it('gera codigo sequencial da pendencia no ano corrente', function () {
    Carbon::setTestNow(Carbon::parse('2026-06-01 10:00:00'));

    PendenciaFactory::new()->create([
        'codigo' => 'PO-2026-0001',
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now(),
    ]);

    $service = app(PendenciaService::class);
    $codigo = $service->gerarCodigo();

    expect($codigo)->toBe('PO-2026-0002');

    Carbon::setTestNow();
});

it('registra atualizacao de status da pendencia', function () {
    $pendencia = PendenciaFactory::new()->create([
        'status' => StatusPendencia::REGISTRADA->value,
    ]);

    $service = app(PendenciaService::class);

    $atualizacao = $service->registrarAtualizacaoStatus(
        $pendencia,
        StatusPendencia::EM_EXECUCAO,
        'teste@gestaosmart.com',
        'Iniciando execução'
    );

    expect($pendencia->fresh()->status)->toBe(StatusPendencia::EM_EXECUCAO)
        ->and($atualizacao->status_anterior)->toBe(StatusPendencia::REGISTRADA)
        ->and($atualizacao->status_novo)->toBe(StatusPendencia::EM_EXECUCAO)
        ->and($atualizacao->comentario)->toBe('Iniciando execução');
});

it('avanca status quando pendencia estiver em etapa nao terminal', function () {
    $pendencia = PendenciaFactory::new()->create([
        'status' => StatusPendencia::REGISTRADA->value,
    ]);

    $service = app(PendenciaService::class);
    $service->avancarStatus($pendencia, 'fluxo-bot', 'Avanço automático');

    $pendencia->refresh();

    expect($pendencia->status)->toBe(StatusPendencia::NOTIFICADA_PRESTADORA)
        ->and($pendencia->atualizacoesStatus()->count())->toBe(1)
        ->and($pendencia->atualizacoesStatus()->latest('id')->first()?->status_novo)
        ->toBe(StatusPendencia::NOTIFICADA_PRESTADORA);
});

it('nao avanca status quando pendencia estiver em status terminal', function () {
    $pendencia = PendenciaFactory::new()->create([
        'status' => StatusPendencia::CONCLUIDA->value,
    ]);

    $service = app(PendenciaService::class);
    $service->avancarStatus($pendencia, 'fluxo-bot', 'Tentativa inválida');

    $pendencia->refresh();

    expect($pendencia->status)->toBe(StatusPendencia::CONCLUIDA)
        ->and($pendencia->atualizacoesStatus()->count())->toBe(0);
});
