<?php

use App\Enums\StatusCronograma;
use App\Models\CronogramaFase;
use App\Models\Projeto;
use App\Services\CronogramaService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

uses(TestCase::class, DatabaseTransactions::class);

it('calcula percentual geral do projeto', function () {
    $projeto = Projeto::factory()->create();

    CronogramaFase::factory()->create([
        'projeto_id' => $projeto->id,
        'percentual_conclusao' => 20,
    ]);

    CronogramaFase::factory()->create([
        'projeto_id' => $projeto->id,
        'percentual_conclusao' => 80,
    ]);

    $service = app(CronogramaService::class);

    expect($service->calcularPercentualGeral($projeto))->toBe(50.0);
});

it('mapeia status textual para enum via reflection', function () {
    $service = app(CronogramaService::class);
    $method = new ReflectionMethod($service, 'mapearStatusTexto');
    $method->setAccessible(true);

    expect($method->invoke($service, 'CONCLUÍDO'))->toBe(StatusCronograma::CONCLUIDO)
        ->and($method->invoke($service, 'EM ANDAMENTO'))->toBe(StatusCronograma::EM_ANDAMENTO)
        ->and($method->invoke($service, 'status desconhecido'))->toBe(StatusCronograma::NAO_INICIADO);
});
