<?php

use App\Enums\FaseCronograma;
use App\Enums\StatusCronograma;
use App\Models\CronogramaFase;
use App\Models\Projeto;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;

uses(DatabaseTransactions::class);

beforeEach(function () {
    setupAdminPanelForTests();
});

it('protege as rotas de cronograma para guest', function () {
    foreach (['cronograma.index', 'cronograma.store', 'cronograma.update', 'cronograma.recalcular'] as $routeName) {
        $middleware = Route::getRoutes()->getByName($routeName)?->gatherMiddleware() ?? [];
        expect($middleware)->toContain('auth');
    }
});

it('retorna index do cronograma autenticado', function () {
    $user = User::factory()->active()->create();
    $projeto = Projeto::factory()->create();

    CronogramaFase::factory()->create([
        'projeto_id' => $projeto->id,
        'fase' => FaseCronograma::VISITA_TECNICA,
        'status' => StatusCronograma::EM_ANDAMENTO,
        'percentual_conclusao' => 40,
    ]);

    $this->actingAs($user)
        ->getJson(route('cronograma.index'))
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.projeto.id', $projeto->id)
        ->assertJsonPath('data.0.fases.0.fase', FaseCronograma::VISITA_TECNICA->value);
});

it('cria fases no store', function () {
    $user = User::factory()->active()->create();
    $projeto = Projeto::factory()->create();

    $this->actingAs($user)
        ->postJson(route('cronograma.store'), ['projeto_id' => $projeto->id])
        ->assertCreated()
        ->assertJsonPath('message', 'Fases criadas com sucesso.');

    $fasesPadraoEsperadas = collect(FaseCronograma::cases())
        ->reject(fn (FaseCronograma $fase): bool => $fase === FaseCronograma::PERSONALIZADA)
        ->count();

    expect(CronogramaFase::query()->where('projeto_id', $projeto->id)->count())
        ->toBe($fasesPadraoEsperadas);
});

it('valida payload inválido no update', function () {
    $user = User::factory()->active()->create();
    $fase = CronogramaFase::factory()->create();

    $this->actingAs($user)
        ->putJson(route('cronograma.update', $fase), [
            'data_prevista_inicio' => '2026-04-20',
            'data_prevista_fim' => '2026-04-10',
            'percentual_conclusao' => 150,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['data_prevista_fim', 'percentual_conclusao']);
});

it('recalcula percentual concluído para 100 no endpoint de recalcular', function () {
    $user = User::factory()->active()->create();
    $projeto = Projeto::factory()->create();

    $fase = CronogramaFase::factory()->create([
        'projeto_id' => $projeto->id,
        'fase' => FaseCronograma::VISITA_TECNICA,
        'status' => StatusCronograma::CONCLUIDO,
        'percentual_conclusao' => 20,
    ]);

    $this->actingAs($user)
        ->postJson(route('cronograma.recalcular', $projeto))
        ->assertOk()
        ->assertJsonPath('message', 'Percentuais sincronizados com sucesso.');

    expect($fase->fresh()->percentual_conclusao)->toBe(100);
});
