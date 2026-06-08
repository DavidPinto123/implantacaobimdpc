<?php

use App\Enums\PosObra\StatusPendencia;
use App\Enums\PosObra\UrgenciaPendencia;
use App\Filament\Resources\PosObra\ConfiguracaoSlaResource;
use App\Filament\Resources\PosObra\DisciplinaConfigResource;
use App\Filament\Resources\PosObra\PendenciaResource;
use App\Models\PosObra\ConfiguracaoSla;
use App\Models\PosObra\DisciplinaConfig;
use App\Models\PosObra\Pendencia;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('cobre CRUD básico da PendenciaResource com persistência por modelo e páginas principais', function () {
    $user = createPrioritariosUserWithPermissions([
        'ViewAny:Pendencia',
        'Create:Pendencia',
        'View:Pendencia',
        'Update:Pendencia',
        'Delete:Pendencia',
    ]);
    $this->actingAs($user);

    $obra = createObraRecord($user);

    $pendencia = Pendencia::create([
        'obras_id' => $obra->id,
        'gestor_id' => $user->id,
        'descricao' => 'Pendência inicial de teste DCT-009',
        'urgencia' => UrgenciaPendencia::P2,
        'status' => StatusPendencia::REGISTRADA,
    ]);

    $pendencia->update([
        'descricao' => 'Pendência atualizada DCT-009',
        'status' => StatusPendencia::EM_EXECUCAO,
    ]);

    $this->get(PendenciaResource::getUrl('index'))->assertOk();
    $this->get(PendenciaResource::getUrl('create'))->assertOk();
    $this->get(PendenciaResource::getUrl('view', ['record' => $pendencia]))->assertOk();
    $this->get(PendenciaResource::getUrl('edit', ['record' => $pendencia]))->assertOk();

    $this->assertDatabaseHas('po_pendencias', [
        'id' => $pendencia->id,
        'descricao' => 'Pendência atualizada DCT-009',
        'status' => StatusPendencia::EM_EXECUCAO->value,
    ]);

    $pendenciaId = $pendencia->id;
    $pendencia->delete();

    $this->assertDatabaseMissing('po_pendencias', ['id' => $pendenciaId]);
});

it('cobre CRUD básico da ConfiguracaoSlaResource com persistência por modelo e páginas principais', function () {
    $user = createPrioritariosUserWithPermissions([
        'ViewAny:ConfiguracaoSla',
        'Create:ConfiguracaoSla',
        'Update:ConfiguracaoSla',
    ]);
    $this->actingAs($user);

    $config = ConfiguracaoSla::create([
        'urgencia' => UrgenciaPendencia::P1,
        'prazo_horas' => 24,
        'ativo' => true,
    ]);

    $config->update([
        'prazo_horas' => 36,
        'ativo' => false,
    ]);

    $this->get(ConfiguracaoSlaResource::getUrl('index'))->assertOk();
    $this->get(ConfiguracaoSlaResource::getUrl('create'))->assertOk();
    $this->get(ConfiguracaoSlaResource::getUrl('edit', ['record' => $config]))->assertOk();

    $this->assertDatabaseHas('po_configuracoes_sla', [
        'id' => $config->id,
        'prazo_horas' => 36,
        'ativo' => false,
    ]);
});

it('cobre CRUD básico da DisciplinaConfigResource com persistência por modelo e páginas principais', function () {
    $user = createPrioritariosUserWithPermissions([
        'ViewAny:DisciplinaConfig',
        'Create:DisciplinaConfig',
        'Update:DisciplinaConfig',
        'Delete:DisciplinaConfig',
    ]);
    $this->actingAs($user);

    $disciplina = DisciplinaConfig::create([
        'codigo' => 'DISC-'.strtoupper(str()->random(6)),
        'label' => 'Disciplina teste DCT-009',
        'ordem' => 10,
        'ativo' => true,
    ]);

    $disciplina->update([
        'label' => 'Disciplina atualizada DCT-009',
        'ordem' => 20,
    ]);

    $this->get(DisciplinaConfigResource::getUrl('index'))->assertOk();
    $this->get(DisciplinaConfigResource::getUrl('create'))->assertOk();
    $this->get(DisciplinaConfigResource::getUrl('edit', ['record' => $disciplina]))->assertOk();

    $this->assertDatabaseHas('po_disciplinas_config', [
        'id' => $disciplina->id,
        'label' => 'Disciplina atualizada DCT-009',
        'ordem' => 20,
    ]);

    $disciplinaId = $disciplina->id;
    $disciplina->delete();

    $this->assertDatabaseMissing('po_disciplinas_config', ['id' => $disciplinaId]);
});
