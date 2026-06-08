<?php

use App\Filament\Pages\ImportCnpjs;
use App\Filament\Pages\ImportObras;
use Database\Factories\ImportacaoLogFactory;
use Database\Factories\ImportacaoStagingFactory;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

it('ImportObras::verificarStatus consolida progresso e staging', function () {
    $log = ImportacaoLogFactory::new()->create([
        'modulo' => 'obras',
        'status' => 'staged',
        'total_linhas' => 10,
        'linhas_criadas' => 3,
        'linhas_atualizadas' => 2,
        'linhas_erro' => 1,
    ]);

    ImportacaoStagingFactory::new()->count(2)->create([
        'importacao_log_id' => $log->id,
        'acao' => 'criar',
    ]);

    ImportacaoStagingFactory::new()->create([
        'importacao_log_id' => $log->id,
        'acao' => 'erro',
    ]);

    $page = new ImportObras;
    $page->importacaoLogId = $log->id;
    $page->stagingFiltro = 'todos';
    $page->stagingPage = 1;
    $page->verificarStatus();

    expect($page->resultado['status'])->toBe('staged')
        ->and($page->resultado['processados'])->toBe(6)
        ->and($page->resultado['percentual'])->toEqual(60.0)
        ->and($page->stagingResumo['criar'])->toBe(2)
        ->and($page->stagingResumo['erro'])->toBe(1);
});

it('ImportCnpjs::verificarStatus usa staging durante processamento', function () {
    $log = ImportacaoLogFactory::new()->create([
        'modulo' => 'cnpjs',
        'status' => 'processando',
        'total_linhas' => 20,
        'linhas_criadas' => 0,
        'linhas_atualizadas' => 0,
        'linhas_erro' => 0,
    ]);

    ImportacaoStagingFactory::new()->count(7)->create([
        'importacao_log_id' => $log->id,
        'acao' => 'criar',
    ]);

    $page = new ImportCnpjs;
    $page->importacaoLogId = $log->id;
    $page->stagingFiltro = 'todos';
    $page->stagingPage = 1;
    $page->verificarStatus();

    expect($page->resultado['status'])->toBe('processando')
        ->and($page->resultado['processados'])->toBe(7)
        ->and($page->resultado['percentual'])->toEqual(35.0)
        ->and($page->stagingResumo['criar'])->toBe(7);
});
