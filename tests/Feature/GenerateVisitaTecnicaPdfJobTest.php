<?php

use App\Jobs\GenerateVisitaTecnicaPdfJob;
use App\Services\VisitaTecnicaPdfService;
use Database\Factories\RelatorioVisitaTecnicaFactory;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

it('limpa a flag de geração ao concluir com sucesso', function () {
    $record = RelatorioVisitaTecnicaFactory::new()->create([
        'pdf_generating_at' => now(),
    ]);

    $service = Mockery::mock(VisitaTecnicaPdfService::class);
    $service->shouldReceive('hasValidStoredPdf')->once()->andReturnFalse();
    $service->shouldReceive('generateAndStorePdf')->once()->andReturn('relatorios-vt/teste.pdf');

    $job = new GenerateVisitaTecnicaPdfJob($record->id);
    $job->handle($service);

    expect($record->fresh()->pdf_generating_at)->toBeNull();
});

it('limpa a flag de geração em caso de falha no handle', function () {
    $record = RelatorioVisitaTecnicaFactory::new()->create([
        'pdf_generating_at' => now(),
    ]);

    $service = Mockery::mock(VisitaTecnicaPdfService::class);
    $service->shouldReceive('hasValidStoredPdf')->once()->andReturnFalse();
    $service->shouldReceive('generateAndStorePdf')->once()->andThrow(new RuntimeException('falhou ao gerar'));

    $job = new GenerateVisitaTecnicaPdfJob($record->id);

    expect(fn () => $job->handle($service))->toThrow(RuntimeException::class);
    expect($record->fresh()->pdf_generating_at)->toBeNull();
});
