<?php

use App\Filament\Resources\RelatorioFotograficos\RelatorioFotograficoResource;
use App\Filament\Resources\RelatorioVisitaTecnicaResource;
use App\Models\RelatorioFotografico;
use App\Models\RelatorioVisitaTecnica;
use Filament\Facades\Filament;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('browser smoke da RelatorioFotograficoResource navega entre list/create/edit sem fluxo pesado', function () {
    $user = createPrioritariosUserWithPermissions(['ViewAny:RelatorioFotografico', 'Create:RelatorioFotografico', 'Update:RelatorioFotografico']);
    $this->actingAs($user);

    $projeto = createPrioritariosProjeto($user);
    $relatorio = RelatorioFotografico::create([
        'projeto_id' => $projeto->id,
        'autor_id' => $user->id,
        'gestor_id' => $user->id,
        'status' => 'draft',
    ]);

    visit(RelatorioFotograficoResource::getUrl('index'))->assertPathIs('/admin/relatorio-fotograficos');
    visit(RelatorioFotograficoResource::getUrl('create'))->assertPathBeginsWith('/admin/relatorio-fotograficos/');
    visit(RelatorioFotograficoResource::getUrl('edit', ['record' => $relatorio]))->assertPathIs("/admin/relatorio-fotograficos/{$relatorio->id}/edit");
});

it('browser smoke da RelatorioVisitaTecnicaResource navega entre list/create/edit sem fluxo pesado', function () {
    $user = createPrioritariosUserWithPermissions(['ViewAny:RelatorioVisitaTecnica', 'Create:RelatorioVisitaTecnica', 'Update:RelatorioVisitaTecnica']);
    $this->actingAs($user);

    $projeto = createPrioritariosProjeto($user);
    $relatorio = RelatorioVisitaTecnica::create([
        'projeto_id' => $projeto->id,
        'numero_relatorio_vt' => 'VT-BROWSER-'.strtoupper(str()->random(6)),
        'autor' => $user->name,
        'status' => 'rascunho',
        'iniciado_em' => now(),
    ]);

    visit(RelatorioVisitaTecnicaResource::getUrl('index'))->assertPathIs('/admin/relatorio-visita-tecnicas');
    visit(RelatorioVisitaTecnicaResource::getUrl('create'))->assertPathBeginsWith('/admin/relatorio-visita-tecnicas/');
    visit(RelatorioVisitaTecnicaResource::getUrl('edit', ['record' => $relatorio]))->assertPathIs("/admin/relatorio-visita-tecnicas/{$relatorio->id}/edit");
});
