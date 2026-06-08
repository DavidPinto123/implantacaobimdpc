<?php

use App\Enums\MotivoAlteracaoObra;
use App\Models\CronogramaFaseHistorico;
use App\Models\Projeto;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('alterar data_posse grava CronogramaFaseHistorico com motivo automático', function () {
    $projeto = Projeto::factory()->create(['data_posse' => '2026-08-01']);

    $antes = CronogramaFaseHistorico::where('projeto_id', $projeto->id)
        ->where('campo_alterado', 'projeto.data_posse')
        ->count();

    $projeto->update(['data_posse' => '2026-09-15']);

    $depois = CronogramaFaseHistorico::where('projeto_id', $projeto->id)
        ->where('campo_alterado', 'projeto.data_posse')
        ->count();

    expect($depois - $antes)->toBe(1);
});

it('histórico de data_posse registra valor_anterior e valor_novo corretos', function () {
    $projeto = Projeto::factory()->create(['data_posse' => '2026-08-01']);

    $projeto->update(['data_posse' => '2026-09-15']);

    $hist = CronogramaFaseHistorico::where('projeto_id', $projeto->id)
        ->where('campo_alterado', 'projeto.data_posse')
        ->latest('id')
        ->first();

    expect($hist->valor_anterior)->toContain('2026-08-01')
        ->and($hist->valor_novo)->toBe('2026-09-15');
});

it('properties transient motivo_codigo e motivo_historico são injetadas no histórico', function () {
    $projeto = Projeto::factory()->create(['data_posse' => '2026-08-01']);

    $projeto->motivo_alteracao_posse_codigo = MotivoAlteracaoObra::ATRASO_ENGENHARIA->value;
    $projeto->motivo_alteracao_posse_historico = 'Engenharia atrasou entrega do shell';
    $projeto->update(['data_posse' => '2026-09-15']);

    $hist = CronogramaFaseHistorico::where('projeto_id', $projeto->id)
        ->where('campo_alterado', 'projeto.data_posse')
        ->latest('id')
        ->first();

    expect($hist->motivo_codigo)->toBe(MotivoAlteracaoObra::ATRASO_ENGENHARIA)
        ->and($hist->motivo_historico)->toBe('Engenharia atrasou entrega do shell');
});

it('properties transient são limpas após gravação para não vazar para próximo save', function () {
    $projeto = Projeto::factory()->create(['data_posse' => '2026-08-01']);

    $projeto->motivo_alteracao_posse_codigo = MotivoAlteracaoObra::LEGALIZACAO->value;
    $projeto->motivo_alteracao_posse_historico = 'Motivo X';
    $projeto->update(['data_posse' => '2026-09-15']);

    expect($projeto->motivo_alteracao_posse_codigo)->toBeNull()
        ->and($projeto->motivo_alteracao_posse_historico)->toBeNull();
});

it('motivo padronizado é casteado para o enum MotivoAlteracaoObra', function () {
    $projeto = Projeto::factory()->create(['data_posse' => '2026-08-01']);
    $projeto->motivo_alteracao_posse_codigo = MotivoAlteracaoObra::SUPPLY->value;
    $projeto->update(['data_posse' => '2026-09-15']);

    $hist = CronogramaFaseHistorico::where('projeto_id', $projeto->id)
        ->where('campo_alterado', 'projeto.data_posse')
        ->latest('id')
        ->first();

    expect($hist->motivo_codigo)->toBeInstanceOf(MotivoAlteracaoObra::class)
        ->and($hist->motivo_codigo)->toBe(MotivoAlteracaoObra::SUPPLY);
});
