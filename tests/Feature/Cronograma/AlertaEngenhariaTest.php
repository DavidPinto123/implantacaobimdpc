<?php

use App\Enums\FaseCronograma;
use App\Models\CronogramaFase;
use App\Models\Projeto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Engenharia']);
    Role::firstOrCreate(['name' => 'PMO']);
    Role::firstOrCreate(['name' => 'Planejamento Estratégico']);

    $this->engenheiro = User::factory()->create(['name' => 'Eng. Teste']);
    $this->engenheiro->assignRole('Engenharia');

    $this->pmo = User::factory()->create(['name' => 'PMO Teste']);
    $this->pmo->assignRole('PMO');
});

it('Engenharia altera data_posse do Projeto → PMO recebe alerta', function () {
    Auth::login($this->engenheiro);
    $projeto = Projeto::factory()->create(['data_posse' => '2026-08-01']);

    $antes = $this->pmo->notifications()->count();

    $projeto->update(['data_posse' => '2026-09-15']);

    // Processa fila (sync — QUEUE_CONNECTION=sync no phpunit.xml)
    $depois = $this->pmo->refresh()->notifications()->count();

    expect($depois - $antes)->toBe(1);
});

it('alerta tem título "Alerta de Engenharia" e cita o nome do autor', function () {
    Auth::login($this->engenheiro);
    $projeto = Projeto::factory()->create(['data_posse' => '2026-08-01']);

    $projeto->update(['data_posse' => '2026-09-15']);

    $notif = $this->pmo->refresh()->notifications()->latest()->first();

    expect($notif->data['title'] ?? '')->toContain('Alerta de Engenharia')
        ->and($notif->data['body'] ?? '')->toContain('Eng. Teste');
});

it('PMO altera data_posse → não dispara alerta', function () {
    Auth::login($this->pmo);
    $projeto = Projeto::factory()->create(['data_posse' => '2026-08-01']);

    $antes = $this->pmo->notifications()->count();

    $projeto->update(['data_posse' => '2026-09-15']);

    $depois = $this->pmo->refresh()->notifications()->count();

    expect($depois - $antes)->toBe(0);
});

it('Engenharia altera data prevista de OBRAS → PMO recebe alerta', function () {
    $projeto = aplicarSmartFit('2026-08-01');
    $obras = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::OBRAS)->first();

    Auth::login($this->engenheiro);

    $antes = $this->pmo->refresh()->notifications()->count();

    $obras->update(['data_prevista_inicio' => $obras->data_prevista_inicio->copy()->addDays(10)]);

    $depois = $this->pmo->refresh()->notifications()->count();

    expect($depois - $antes)->toBeGreaterThanOrEqual(1);
});

it('Engenharia altera fase NÃO-crítica → não dispara alerta', function () {
    $projeto = aplicarSmartFit('2026-08-01');
    $cad = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::LEVANTAMENTO_CADASTRAL)->first();

    Auth::login($this->engenheiro);
    $antes = $this->pmo->refresh()->notifications()->count();

    $cad->update(['data_prevista_inicio' => $cad->data_prevista_inicio->copy()->addDays(3)]);

    $depois = $this->pmo->refresh()->notifications()->count();

    expect($depois - $antes)->toBe(0);
});

it('Engenharia altera apenas status (sem mudar data) → não dispara alerta', function () {
    $projeto = aplicarSmartFit('2026-08-01');
    $obras = CronogramaFase::where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::OBRAS)->first();

    Auth::login($this->engenheiro);
    $antes = $this->pmo->refresh()->notifications()->count();

    $obras->update(['observacoes' => 'Apenas comentário, sem mudar prazo']);

    $depois = $this->pmo->refresh()->notifications()->count();

    expect($depois - $antes)->toBe(0);
});
