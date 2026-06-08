<?php

use App\Models\Projeto;
use App\Support\CronogramaLimites;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('expõe constantes dos limites operacionais', function () {
    expect(CronogramaLimites::DIAS_MIN_BRIEFING_INICIO_OBRAS)->toBe(75)
        ->and(CronogramaLimites::DIAS_IDEAL_INICIO_PROJETO_POSSE)->toBe(83);
});

it('diasEntre calcula a diferença correta entre datas', function () {
    $de = Carbon::parse('2026-01-01');
    $ate = Carbon::parse('2026-01-11');

    expect(CronogramaLimites::diasEntre($de, $ate))->toBe(10);
});

it('diasEntre retorna null se alguma data estiver vazia', function () {
    expect(CronogramaLimites::diasEntre(null, Carbon::now()))->toBeNull()
        ->and(CronogramaLimites::diasEntre(Carbon::now(), null))->toBeNull();
});

it('avaliar marca violado quando Início→Posse fica abaixo de 83 dias', function () {
    $projeto = Projeto::factory()->create([
        'cad_plan_inicio' => '2026-01-01',
        'data_posse' => '2026-02-14', // 44 dias
    ]);

    $resumo = CronogramaLimites::avaliar($projeto);

    expect($resumo['inicio_posse']['violado'])->toBeTrue()
        ->and($resumo['inicio_posse']['dias_atuais'])->toBe(44)
        ->and($resumo['inicio_posse']['limite'])->toBe(83)
        ->and($resumo['inicio_posse']['mensagem'])->toContain('44 dias')
        ->and($resumo['inicio_posse']['mensagem'])->toContain('83 dias');
});

it('avaliar não marca violado quando Início→Posse está acima de 83 dias', function () {
    $projeto = Projeto::factory()->create([
        'cad_plan_inicio' => '2026-01-01',
        'data_posse' => '2026-05-01', // 120 dias
    ]);

    $resumo = CronogramaLimites::avaliar($projeto);

    expect($resumo['inicio_posse']['violado'])->toBeFalse()
        ->and($resumo['inicio_posse']['mensagem'])->toBeNull();
});

it('avaliar marca violado quando Briefing→Obras fica abaixo de 75 dias', function () {
    $projeto = Projeto::factory()->create([
        'brief_plan_lay_inicio' => '2026-01-01',
        'inicio_obra' => '2026-02-15', // 45 dias
    ]);

    $resumo = CronogramaLimites::avaliar($projeto);

    expect($resumo['briefing_obras']['violado'])->toBeTrue()
        ->and($resumo['briefing_obras']['dias_atuais'])->toBe(45);
});

it('temViolacao retorna true se qualquer um dos dois cenários estiver violado', function () {
    $projeto = Projeto::factory()->create([
        'cad_plan_inicio' => '2026-01-01',
        'data_posse' => '2026-02-14', // 44 dias — violado
        'brief_plan_lay_inicio' => '2026-01-01',
        'inicio_obra' => '2026-05-01', // 120 dias — OK
    ]);

    expect(CronogramaLimites::temViolacao($projeto))->toBeTrue();
});

it('temViolacao retorna false quando ambos os cenários estão dentro do limite', function () {
    $projeto = Projeto::factory()->create([
        'cad_plan_inicio' => '2026-01-01',
        'data_posse' => '2026-05-01', // 120 dias
        'brief_plan_lay_inicio' => '2026-01-01',
        'inicio_obra' => '2026-05-01',
    ]);

    expect(CronogramaLimites::temViolacao($projeto))->toBeFalse();
});

it('avaliar com datas vazias retorna violado=false (sem dados não viola)', function () {
    $projeto = Projeto::factory()->create([
        'cad_plan_inicio' => null,
        'data_posse' => null,
        'brief_plan_lay_inicio' => null,
        'inicio_obra' => null,
    ]);

    $resumo = CronogramaLimites::avaliar($projeto);

    expect($resumo['inicio_posse']['violado'])->toBeFalse()
        ->and($resumo['briefing_obras']['violado'])->toBeFalse();
});
