<?php

use App\Enums\MotivoAlteracaoObra;
use App\Filament\Pages\HistoricoUnidades;
use App\Models\CronogramaFaseHistorico;
use App\Models\Projeto;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'super_admin']);
    $this->user = User::factory()->create();
    $this->user->assignRole('super_admin');
    $this->actingAs($this->user);
    Filament::setCurrentPanel('admin');
});

it('página /admin/historico-unidades renderiza sem erro', function () {
    Livewire::test(HistoricoUnidades::class)
        ->assertOk();
});

it('lista apenas alterações em campo_alterado=projeto.data_posse', function () {
    $projeto = Projeto::factory()->create();

    // Cria entries em campos distintos
    CronogramaFaseHistorico::create([
        'projeto_id' => $projeto->id,
        'cronograma_fase_id' => null,
        'campo_alterado' => 'projeto.data_posse',
        'valor_anterior' => '2026-01-01',
        'valor_novo' => '2026-02-01',
        'motivo' => 'Teste posse',
        'motivo_codigo' => MotivoAlteracaoObra::ATRASO_ENGENHARIA->value,
        'usuario_id' => $this->user->id,
        'automatico' => false,
    ]);
    CronogramaFaseHistorico::create([
        'projeto_id' => $projeto->id,
        'cronograma_fase_id' => null,
        'campo_alterado' => 'data_prevista_inicio',
        'valor_anterior' => '2026-03-01',
        'valor_novo' => '2026-03-15',
        'motivo' => 'Teste outro campo',
        'usuario_id' => $this->user->id,
        'automatico' => false,
    ]);

    Livewire::test(HistoricoUnidades::class)
        ->assertCanSeeTableRecords(
            CronogramaFaseHistorico::where('campo_alterado', 'projeto.data_posse')->get()
        )
        ->assertCanNotSeeTableRecords(
            CronogramaFaseHistorico::where('campo_alterado', 'data_prevista_inicio')->get()
        );
});

it('filtro por motivo_codigo filtra registros corretamente', function () {
    $projeto = Projeto::factory()->create();

    $hAtraso = CronogramaFaseHistorico::create([
        'projeto_id' => $projeto->id,
        'campo_alterado' => 'projeto.data_posse',
        'valor_anterior' => '2026-01-01',
        'valor_novo' => '2026-02-01',
        'motivo' => 'atraso eng',
        'motivo_codigo' => MotivoAlteracaoObra::ATRASO_ENGENHARIA->value,
        'usuario_id' => $this->user->id,
        'automatico' => false,
    ]);
    $hSupply = CronogramaFaseHistorico::create([
        'projeto_id' => $projeto->id,
        'campo_alterado' => 'projeto.data_posse',
        'valor_anterior' => '2026-01-01',
        'valor_novo' => '2026-02-15',
        'motivo' => 'supply',
        'motivo_codigo' => MotivoAlteracaoObra::SUPPLY->value,
        'usuario_id' => $this->user->id,
        'automatico' => false,
    ]);

    Livewire::test(HistoricoUnidades::class)
        ->filterTable('motivo_codigo', [MotivoAlteracaoObra::ATRASO_ENGENHARIA->value])
        ->assertCanSeeTableRecords([$hAtraso])
        ->assertCanNotSeeTableRecords([$hSupply]);
});
