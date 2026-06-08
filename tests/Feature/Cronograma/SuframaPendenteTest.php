<?php

use App\Enums\FaseCronograma;
use App\Models\CronogramaFase;
use App\Models\Projeto;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('suframaPendente retorna false quando aplicavel_suframa já foi decidido (true)', function () {
    $projeto = Projeto::factory()->create([
        'data_posse' => '2026-08-01',
        'aplicavel_suframa' => true,
    ]);

    expect($projeto->suframaPendente())->toBeFalse();
});

it('suframaPendente retorna false quando aplicavel_suframa já foi decidido (false)', function () {
    $projeto = Projeto::factory()->create([
        'data_posse' => '2026-08-01',
        'aplicavel_suframa' => false,
    ]);

    expect($projeto->suframaPendente())->toBeFalse();
});

it('suframaPendente retorna true quando aplicavel_suframa=null e faltam <=60 dias para inauguração', function () {
    $projeto = aplicarSmartFit('2026-08-01');
    $projeto->update(['aplicavel_suframa' => null]);

    CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::INAUGURACAO)
        ->update(['data_prevista_inicio' => now()->addDays(50)->toDateString()]);

    expect($projeto->refresh()->suframaPendente())->toBeTrue();
});

it('suframaPendente retorna false quando faltam mais de 60 dias para inauguração', function () {
    $projeto = aplicarSmartFit('2026-08-01');
    $projeto->update(['aplicavel_suframa' => null]);

    CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::INAUGURACAO)
        ->update(['data_prevista_inicio' => now()->addDays(120)->toDateString()]);

    expect($projeto->refresh()->suframaPendente())->toBeFalse();
});

it('suframaPendente retorna false quando inauguração já passou', function () {
    $projeto = aplicarSmartFit('2026-08-01');
    $projeto->update(['aplicavel_suframa' => null]);

    CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::INAUGURACAO)
        ->update(['data_prevista_inicio' => now()->subDays(5)->toDateString()]);

    expect($projeto->refresh()->suframaPendente())->toBeFalse();
});

it('diasParaInauguracao retorna o número de dias até a inauguração prevista', function () {
    $projeto = aplicarSmartFit('2026-08-01');

    CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::INAUGURACAO)
        ->update(['data_prevista_inicio' => now()->addDays(45)->toDateString()]);

    expect($projeto->refresh()->diasParaInauguracao())->toBe(45);
});
