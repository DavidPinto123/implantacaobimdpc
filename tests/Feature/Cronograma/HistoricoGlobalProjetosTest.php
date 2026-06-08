<?php

use App\Enums\MotivoAlteracaoObra;
use App\Filament\Pages\HistoricoProjetos;
use App\Models\CronogramaFase;
use App\Models\CronogramaFaseHistorico;
use App\Models\Projeto;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'super_admin']);
    $this->user = User::factory()->create();
    $this->user->assignRole('super_admin');
    Auth::login($this->user);
    Filament::setCurrentPanel('admin');
});

it('lista registros de histórico de múltiplos projetos', function () {
    $a = Projeto::factory()->create();
    $b = Projeto::factory()->create();

    CronogramaFaseHistorico::create([
        'projeto_id' => $a->id,
        'cronograma_fase_id' => null,
        'campo_alterado' => 'projeto.data_posse',
        'valor_anterior' => '2026-01-01',
        'valor_novo' => '2026-02-01',
        'motivo' => 'Posse projeto A',
        'usuario_id' => $this->user->id,
        'automatico' => false,
    ]);
    CronogramaFaseHistorico::create([
        'projeto_id' => $b->id,
        'cronograma_fase_id' => null,
        'campo_alterado' => 'projeto.data_posse',
        'valor_anterior' => '2026-03-01',
        'valor_novo' => '2026-04-01',
        'motivo' => 'Posse projeto B',
        'usuario_id' => $this->user->id,
        'automatico' => false,
    ]);

    $page = new HistoricoProjetos;
    $data = $page->getViewData();

    expect($data['totalRegistros'])->toBe(2)
        ->and(count($data['projetos']))->toBe(2);
});

it('filtra histórico por projeto via filtroProjetoId', function () {
    $a = Projeto::factory()->create();
    $b = Projeto::factory()->create();

    CronogramaFaseHistorico::create([
        'projeto_id' => $a->id,
        'campo_alterado' => 'projeto.data_posse',
        'valor_anterior' => '2026-01-01',
        'valor_novo' => '2026-02-01',
        'motivo' => 'A',
        'usuario_id' => $this->user->id,
        'automatico' => false,
    ]);
    CronogramaFaseHistorico::create([
        'projeto_id' => $b->id,
        'campo_alterado' => 'projeto.data_posse',
        'valor_anterior' => '2026-03-01',
        'valor_novo' => '2026-04-01',
        'motivo' => 'B',
        'usuario_id' => $this->user->id,
        'automatico' => false,
    ]);

    $page = new HistoricoProjetos;
    $page->filtroProjetoId = $a->id;
    $data = $page->getViewData();

    expect($data['totalRegistros'])->toBe(1)
        ->and($data['registros']->first()->motivo)->toBe('A');
});

it('filtra histórico por usuário', function () {
    Role::firstOrCreate(['name' => 'Engenharia']);
    $outroUser = User::factory()->create();
    $outroUser->assignRole('Engenharia');

    $projeto = Projeto::factory()->create();

    CronogramaFaseHistorico::create([
        'projeto_id' => $projeto->id,
        'campo_alterado' => 'projeto.data_posse',
        'valor_anterior' => '2026-01-01',
        'valor_novo' => '2026-02-01',
        'motivo' => 'mudança 1',
        'usuario_id' => $this->user->id,
        'automatico' => false,
    ]);
    CronogramaFaseHistorico::create([
        'projeto_id' => $projeto->id,
        'campo_alterado' => 'projeto.data_posse',
        'valor_anterior' => '2026-02-01',
        'valor_novo' => '2026-03-01',
        'motivo' => 'mudança 2',
        'usuario_id' => $outroUser->id,
        'automatico' => false,
    ]);

    $page = new HistoricoProjetos;
    $page->filtroUsuarioId = $outroUser->id;
    $data = $page->getViewData();

    expect($data['totalRegistros'])->toBe(1)
        ->and($data['registros']->first()->motivo)->toBe('mudança 2');
});

it('agrupa registros do mesmo lote (mesmo timestamp + motivo + usuário)', function () {
    $projeto = Projeto::factory()->create();
    $ts = now();

    foreach ([1, 2, 3] as $i) {
        CronogramaFaseHistorico::create([
            'projeto_id' => $projeto->id,
            'cronograma_fase_id' => null,
            'campo_alterado' => 'data_prevista_inicio',
            'valor_anterior' => '2026-01-0'.$i,
            'valor_novo' => '2026-02-0'.$i,
            'motivo' => 'Lote único',
            'usuario_id' => $this->user->id,
            'automatico' => false,
            'created_at' => $ts,
        ]);
    }

    $page = new HistoricoProjetos;
    $page->filtroProjetoId = $projeto->id;
    $data = $page->getViewData();

    expect($data['totalRegistros'])->toBe(3)
        ->and($data['lotes']->count())->toBe(1);
});

it('limparFiltros zera todos os filtros', function () {
    $page = new HistoricoProjetos;
    $page->filtroProjetoId = 99;
    $page->filtroUsuarioId = 88;
    $page->filtroCampo = 'projeto.data_posse';
    $page->filtroDataInicio = '2026-01-01';
    $page->filtroDataFim = '2026-12-31';

    $page->limparFiltros();

    expect($page->filtroProjetoId)->toBeNull()
        ->and($page->filtroUsuarioId)->toBeNull()
        ->and($page->filtroCampo)->toBeNull()
        ->and($page->filtroDataInicio)->toBeNull()
        ->and($page->filtroDataFim)->toBeNull();
});
