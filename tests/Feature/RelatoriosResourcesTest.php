<?php

use App\Filament\Resources\RelatorioFotograficos\RelatorioFotograficoResource;
use App\Filament\Resources\RelatorioVisitaTecnicaResource;
use App\Models\RelatorioFotografico;
use App\Models\RelatorioVisitaTecnica;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('cobre CRUD básico da RelatorioFotograficoResource com create realista por draft/model fallback', function () {
    $user = createPrioritariosUserWithPermissions(['ViewAny:RelatorioFotografico', 'Create:RelatorioFotografico', 'Update:RelatorioFotografico', 'View:RelatorioFotografico']);
    $this->actingAs($user);

    $projeto = createPrioritariosProjeto($user);

    $relatorio = RelatorioFotografico::create([
        'projeto_id' => $projeto->id,
        'autor_id' => $user->id,
        'gestor_id' => $user->id,
        'status' => 'draft',
        'status_relatorio' => 'rascunho',
    ]);

    $relatorio->update(['status' => 'concluido']);

    $this->get(RelatorioFotograficoResource::getUrl('index'))->assertOk();
    $this->get(RelatorioFotograficoResource::getUrl('create'))->assertRedirect();
    $this->get(RelatorioFotograficoResource::getUrl('edit', ['record' => $relatorio]))->assertOk();

    $this->assertDatabaseHas('relatorio_fotograficos', ['id' => $relatorio->id, 'status' => 'concluido']);
});

it('cobre CRUD básico da RelatorioVisitaTecnicaResource com create realista por draft/model fallback', function () {
    $user = createPrioritariosUserWithPermissions(['ViewAny:RelatorioVisitaTecnica', 'Create:RelatorioVisitaTecnica', 'Update:RelatorioVisitaTecnica', 'View:RelatorioVisitaTecnica']);
    $this->actingAs($user);

    $projeto = createPrioritariosProjeto($user);

    $relatorio = RelatorioVisitaTecnica::create([
        'projeto_id' => $projeto->id,
        'numero_relatorio_vt' => 'VT-'.strtoupper(str()->random(8)),
        'autor' => $user->name,
        'status' => 'rascunho',
        'iniciado_em' => now(),
    ]);

    $relatorio->update(['status' => 'concluido']);

    $this->get(RelatorioVisitaTecnicaResource::getUrl('index'))->assertOk();
    $this->get(RelatorioVisitaTecnicaResource::getUrl('create'))->assertRedirect();
    $this->get(RelatorioVisitaTecnicaResource::getUrl('edit', ['record' => $relatorio]))->assertOk();

    $this->assertDatabaseHas('relatorio_visita_tecnicas', ['id' => $relatorio->id, 'status' => 'concluido']);
});
