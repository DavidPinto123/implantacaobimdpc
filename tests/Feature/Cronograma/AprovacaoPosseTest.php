<?php

use App\Enums\MotivoAlteracaoObra;
use App\Filament\Pages\AprovacaoMudancaPosse;
use App\Models\CronogramaFaseHistorico;
use App\Models\Projeto;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'PMO']);
    Role::firstOrCreate(['name' => 'Engenharia']);
    $this->pmo = User::factory()->create();
    $this->pmo->assignRole('PMO');
    $this->engenharia = User::factory()->create();
    $this->engenharia->assignRole('Engenharia');
    Filament::setCurrentPanel('admin');
});

it('PMO consegue aprovar mudança da Data de Posse pendente', function () {
    Auth::login($this->pmo);

    $projeto = Projeto::factory()->create(['data_posse' => '2026-08-01']);
    $projeto->update([
        'data_posse_pendente' => '2026-09-15',
        'data_posse_pendente_motivo' => 'Atraso no Shell',
        'data_posse_pendente_motivo_codigo' => MotivoAlteracaoObra::ATRASO_SHELL_DOCUMENTACAO_PP->value,
        'data_posse_pendente_user_id' => $this->engenharia->id,
        'data_posse_pendente_solicitada_em' => now(),
    ]);

    $page = new AprovacaoMudancaPosse;
    $page->aprovar($projeto->id);

    $projeto->refresh();

    expect($projeto->data_posse->toDateString())->toBe('2026-09-15')
        ->and($projeto->data_posse_pendente)->toBeNull()
        ->and($projeto->data_posse_pendente_motivo)->toBeNull();
});

it('PMO consegue rejeitar mudança da Data de Posse, mantendo data atual', function () {
    Auth::login($this->pmo);

    $projeto = Projeto::factory()->create(['data_posse' => '2026-08-01']);
    $projeto->update([
        'data_posse_pendente' => '2026-09-15',
        'data_posse_pendente_motivo' => 'Atraso',
        'data_posse_pendente_motivo_codigo' => MotivoAlteracaoObra::SUPPLY->value,
        'data_posse_pendente_user_id' => $this->engenharia->id,
        'data_posse_pendente_solicitada_em' => now(),
    ]);

    $page = new AprovacaoMudancaPosse;
    $page->rejeitar($projeto->id, 'Não justificado');

    $projeto->refresh();

    expect($projeto->data_posse->toDateString())->toBe('2026-08-01')
        ->and($projeto->data_posse_pendente)->toBeNull();
});

it('rejeição grava entry no histórico com motivo "Rejeição de mudança Data da Posse"', function () {
    Auth::login($this->pmo);

    $projeto = Projeto::factory()->create(['data_posse' => '2026-08-01']);
    $projeto->update([
        'data_posse_pendente' => '2026-09-15',
        'data_posse_pendente_motivo_codigo' => MotivoAlteracaoObra::SUPPLY->value,
        'data_posse_pendente_user_id' => $this->engenharia->id,
        'data_posse_pendente_solicitada_em' => now(),
    ]);

    (new AprovacaoMudancaPosse)->rejeitar($projeto->id, 'Sem prova de atraso');

    $hist = CronogramaFaseHistorico::where('projeto_id', $projeto->id)
        ->where('motivo', 'like', 'Rejeição de mudança Data da Posse%')
        ->first();

    expect($hist)->not->toBeNull()
        ->and($hist->motivo)->toContain('Sem prova de atraso');
});

it('getViewData retorna apenas projetos com data_posse_pendente', function () {
    Auth::login($this->pmo);

    Projeto::factory()->create(['data_posse' => '2026-08-01']);
    $pendente = Projeto::factory()->create(['data_posse' => '2026-08-01']);
    $pendente->update([
        'data_posse_pendente' => '2026-09-15',
        'data_posse_pendente_user_id' => $this->engenharia->id,
        'data_posse_pendente_solicitada_em' => now(),
    ]);

    $page = new AprovacaoMudancaPosse;
    $data = $page->getViewData();

    expect($data['projetos'])->toHaveCount(1)
        ->and($data['projetos']->first()->id)->toBe($pendente->id);
});
