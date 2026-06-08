<?php

use App\Enums\FaseCronograma;
use App\Enums\StatusCronograma;
use App\Filament\Pages\CadastrarPonto;
use App\Models\CronogramaFase;
use App\Models\Etapa;
use App\Models\Projeto;
use App\Models\Setor;
use App\Models\Task;
use App\Models\TaskCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

beforeEach(function () {
    setupAdminPanelForTests();
    ensureDefaultRoles();
});

it('faz sync reverso de CronogramaFase para Projeto em fase canônica', function () {
    $projeto = Projeto::factory()->create([
        'vis_plan_inicio' => null,
        'vis_rea_fim' => null,
    ]);

    $fase = CronogramaFase::factory()->create([
        'projeto_id' => $projeto->id,
        'fase' => FaseCronograma::VISITA_TECNICA,
        'status' => StatusCronograma::EM_ANDAMENTO,
    ]);

    $fase->update([
        'data_prevista_inicio' => '2026-05-01',
        'data_realizada_fim' => '2026-05-20',
    ]);

    $projeto->refresh();

    expect($projeto->vis_plan_inicio?->format('Y-m-d'))->toBe('2026-05-01')
        ->and($projeto->vis_rea_fim?->format('Y-m-d'))->toBe('2026-05-20');
});

it('observer ajusta status concluído com datas e percentual', function () {
    $fase = CronogramaFase::factory()->create([
        'status' => StatusCronograma::NAO_INICIADO,
        'percentual_conclusao' => 15,
        'data_realizada_inicio' => null,
        'data_realizada_fim' => null,
    ]);

    $fase->update(['status' => StatusCronograma::CONCLUIDO]);

    $fase->refresh();

    expect($fase->percentual_conclusao)->toBe(100)
        ->and($fase->data_realizada_inicio)->not->toBeNull()
        ->and($fase->data_realizada_fim)->not->toBeNull();
});

it('observer limpa datas em status inicial e marca início em andamento', function () {
    $fase = CronogramaFase::factory()->create([
        'status' => StatusCronograma::CONCLUIDO,
        'data_realizada_inicio' => now()->subDay(),
        'data_realizada_fim' => now(),
    ]);

    $fase->update(['status' => StatusCronograma::NAO_INICIADO]);
    $fase->refresh();

    expect($fase->data_realizada_inicio)->toBeNull()
        ->and($fase->data_realizada_fim)->toBeNull();

    $fase->update(['status' => StatusCronograma::EM_ANDAMENTO]);
    $fase->refresh();

    expect($fase->data_realizada_inicio)->not->toBeNull()
        ->and($fase->data_realizada_fim)->toBeNull();
});

it('cadastrar ponto cria task quando vis_status for sim', function () {
    $user = createActiveUserWithPermissions([], [
        'email' => 'admin.cronograma@example.com',
    ]);
    $this->actingAs($user);

    Etapa::firstOrCreate(['nome' => 'Prospecção']);
    TaskCategory::query()->create(['name' => 'Visita Técnica']);
    Setor::firstOrCreate(['setor' => 'Obras']);

    User::factory()->active()->create(['email' => 'talita.carmona@bioritmo.com.br']);
    User::factory()->active()->create(['email' => 'talita.soares@smartfit.com']);

    ['pais' => $pais, 'estado' => $estado, 'cidade' => $cidade] = createLocationDependencies();

    $page = new class extends CadastrarPonto
    {
        public function persistForTest(array $data): void
        {
            $this->persistCreate($data);
        }
    };

    $page->persistForTest([
        'nome' => 'Ponto Cronograma',
        'marca' => 'Smart Fit',
        'cidade_id' => $cidade->id,
        'estado_id' => $estado->id,
        'pais_id' => $pais->id,
        'rua' => 'Rua Teste',
        'numero' => '123',
        'bairro' => 'Centro',
        'vis_status' => 'sim',
    ]);

    $projeto = Projeto::query()->where('nome', 'Ponto Cronograma')->latest('id')->first();

    expect($projeto)->not->toBeNull();

    $task = Task::query()->where('projeto_id', $projeto->id)->first();

    expect($task)->not->toBeNull()
        ->and($task->title)->toContain('Realizar visita técnica');
});
