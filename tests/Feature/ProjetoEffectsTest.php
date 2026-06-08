<?php

use App\Enums\FaseCronograma;
use App\Models\Cidade;
use App\Models\CronogramaFase;
use App\Models\Estado;
use App\Models\Etapa;
use App\Models\HistoricoProjeto;
use App\Models\Pais;
use App\Models\Projeto;
use App\Models\User;
use App\Support\DateCalc;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

function createProjetoBaseDependencies(): array
{
    $pais = Pais::create(['nome' => 'Brasil']);
    $estado = Estado::create(['nome' => 'São Paulo', 'uf' => 'SP', 'pais_id' => $pais->id]);
    $cidade = Cidade::create(['nome' => 'São Paulo', 'estado_id' => $estado->id]);
    $etapa = Etapa::create(['nome' => 'Prospecção']);

    return compact('pais', 'estado', 'cidade', 'etapa');
}

function createProjetoWithRelations(User $user, array $overrides = []): Projeto
{
    ['pais' => $pais, 'estado' => $estado, 'cidade' => $cidade, 'etapa' => $etapa] = createProjetoBaseDependencies();

    return Projeto::create(array_merge([
        'nome' => 'Projeto Efeitos Teste',
        'sigla' => 'PET',
        'status' => 'Em processo',
        'user_id' => $user->id,
        'etapa_id' => $etapa->id,
        'cidade_id' => $cidade->id,
        'estado_id' => $estado->id,
        'pais_id' => $pais->id,
        'rua' => 'Rua Teste',
        'bairro' => 'Centro',
        'cep' => '01000-000',
        'numero' => '123',
    ], $overrides));
}

it('cria entrada inicial de histórico com ação criado quando projeto é criado', function () {
    $user = User::factory()->active()->create();
    $this->actingAs($user);

    $projeto = createProjetoWithRelations($user);

    $historico = HistoricoProjeto::query()
        ->where('projeto_id', $projeto->id)
        ->where('acao', 'criado')
        ->first();

    expect($historico)->not->toBeNull()
        ->and($historico->usuario_id)->toBe($user->id)
        ->and($historico->status_novo)->toBe($projeto->status);
});

it('cria entrada de histórico com ação alterou_status quando status do projeto muda', function () {
    $user = User::factory()->active()->create();
    $this->actingAs($user);

    $projeto = createProjetoWithRelations($user, ['status' => 'Em processo']);

    $projeto->update(['status' => 'Obras']);

    $historico = HistoricoProjeto::query()
        ->where('projeto_id', $projeto->id)
        ->where('acao', 'alterou_status')
        ->latest('id')
        ->first();

    expect($historico)->not->toBeNull()
        ->and($historico->status_antigo)->toBe('Em processo')
        ->and($historico->status_novo)->toBe('Obras');
});

it('sincroniza campos legados do cronograma para cronograma_fases via observer de projeto', function () {
    $user = User::factory()->active()->create();
    $this->actingAs($user);

    $projeto = createProjetoWithRelations($user);

    $expectedEnd = DateCalc::endDate('2026-04-01', 4, false);

    $projeto->update([
        'vis_plan_inicio' => '2026-04-01',
        'vis_plan_dias' => 4,
    ]);

    $fase = CronogramaFase::query()
        ->where('projeto_id', $projeto->id)
        ->where('fase', FaseCronograma::VISITA_TECNICA)
        ->first();

    expect($fase)->not->toBeNull()
        ->and($fase->data_prevista_inicio?->format('Y-m-d'))->toBe('2026-04-01')
        ->and($fase->data_prevista_fim?->format('Y-m-d'))->toBe($expectedEnd);
});

it('calcula data final derivada a partir de data inicial e dias ao salvar projeto', function () {
    $user = User::factory()->active()->create();
    $this->actingAs($user);

    $start = '2026-04-10';
    $days = 7;

    $projeto = createProjetoWithRelations($user, [
        'cad_plan_inicio' => $start,
        'cad_plan_dias' => $days,
    ]);

    $expectedEnd = DateCalc::endDate($start, $days, false);

    expect($projeto->fresh()->cad_plan_fim?->format('Y-m-d'))->toBe($expectedEnd);
});
