<?php

use App\Jobs\ProcessCnpjImportJob;
use App\Jobs\ProcessObraImportJob;
use App\Models\ImportacaoLog;
use Database\Factories\ImportacaoLogFactory;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

it('marca ImportacaoLog como erro fatal quando ProcessObraImportJob falha', function () {
    $log = ImportacaoLogFactory::new()->create([
        'modulo' => 'obras',
        'status' => 'processando',
    ]);

    $job = new ProcessObraImportJob($log->id, $log->user_id);
    $job->failed(new RuntimeException('falha fatal obra'));

    $log->refresh();

    expect($log->status)->toBe('erro')
        ->and($log->finalizado_em)->not->toBeNull()
        ->and($log->erros)->toBe([
            ['linha' => 0, 'msg' => 'falha fatal obra', 'tipo' => 'fatal'],
        ]);
});

it('marca ImportacaoLog como erro fatal quando ProcessCnpjImportJob falha', function () {
    $log = ImportacaoLogFactory::new()->create([
        'modulo' => 'cnpjs',
        'status' => 'processando',
    ]);

    $job = new ProcessCnpjImportJob($log->id, $log->user_id);
    $job->failed(new RuntimeException('falha fatal cnpj'));

    /** @var ImportacaoLog $log */
    $log = $log->fresh();

    expect($log->status)->toBe('erro')
        ->and($log->finalizado_em)->not->toBeNull()
        ->and($log->erros)->toBe([
            ['linha' => 0, 'msg' => 'falha fatal cnpj', 'tipo' => 'fatal'],
        ]);
});
