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

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('browser smoke da PendenciaResource navega entre list/create/view/edit', function () {
    $user = createPrioritariosUserWithPermissions([
        'ViewAny:Pendencia',
        'Create:Pendencia',
        'View:Pendencia',
        'Update:Pendencia',
    ]);
    $this->actingAs($user);

    $obra = createObraRecord($user);
    $pendencia = Pendencia::create([
        'obras_id' => $obra->id,
        'gestor_id' => $user->id,
        'descricao' => 'Pendência browser DCT-009',
        'urgencia' => UrgenciaPendencia::P3,
        'status' => StatusPendencia::REGISTRADA,
    ]);

    visit(PendenciaResource::getUrl('index'))->assertPathIs('/admin/pos-obra/pendencias');
    visit(PendenciaResource::getUrl('create'))->assertPathIs('/admin/pos-obra/pendencias/create');
    visit(PendenciaResource::getUrl('view', ['record' => $pendencia]))->assertPathIs("/admin/pos-obra/pendencias/{$pendencia->id}");
    visit(PendenciaResource::getUrl('edit', ['record' => $pendencia]))->assertPathIs("/admin/pos-obra/pendencias/{$pendencia->id}/edit");
});

it('browser smoke da ConfiguracaoSlaResource navega entre list/create/edit', function () {
    $user = createPrioritariosUserWithPermissions([
        'ViewAny:ConfiguracaoSla',
        'Create:ConfiguracaoSla',
        'Update:ConfiguracaoSla',
    ]);
    $this->actingAs($user);

    $config = ConfiguracaoSla::create([
        'urgencia' => UrgenciaPendencia::P2,
        'prazo_horas' => 48,
        'ativo' => true,
    ]);

    visit(ConfiguracaoSlaResource::getUrl('index'))->assertPathIs('/admin/pos-obra/configuracao-slas');
    visit(ConfiguracaoSlaResource::getUrl('create'))->assertPathIs('/admin/pos-obra/configuracao-slas/create');
    visit(ConfiguracaoSlaResource::getUrl('edit', ['record' => $config]))->assertPathIs("/admin/pos-obra/configuracao-slas/{$config->id}/edit");
});

it('browser smoke da DisciplinaConfigResource navega entre list/create/edit', function () {
    $user = createPrioritariosUserWithPermissions([
        'ViewAny:DisciplinaConfig',
        'Create:DisciplinaConfig',
        'Update:DisciplinaConfig',
    ]);
    $this->actingAs($user);

    $disciplina = DisciplinaConfig::create([
        'codigo' => 'DISC-BROWSER-'.strtoupper(str()->random(4)),
        'label' => 'Disciplina Browser DCT-009',
        'ordem' => 5,
        'ativo' => true,
    ]);

    visit(DisciplinaConfigResource::getUrl('index'))->assertPathIs('/admin/pos-obra/disciplina-configs');
    visit(DisciplinaConfigResource::getUrl('create'))->assertPathIs('/admin/pos-obra/disciplina-configs/create');
    visit(DisciplinaConfigResource::getUrl('edit', ['record' => $disciplina]))->assertPathIs("/admin/pos-obra/disciplina-configs/{$disciplina->id}/edit");
});
