<?php

use App\Filament\Resources\ObraDocumentos\ObraDocumentoResource;
use App\Filament\Resources\ObraDocumentos\Pages\ListObraDocumentos;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

beforeEach(function () {
    setupAdminPanelForTests();
});

it('cobre fluxo real de documento de obra (listagem e edicao sem exclusao)', function () {
    $user = createResourceBaselineUser([
        'ViewAny:AutorizacaoServico',
        'View:AutorizacaoServico',
        'Update:AutorizacaoServico',
    ]);

    setGestorObras($user);

    $this->actingAs($user);

    $obra = createObraRecord($user);
    $documento = createObraDocumentoRecord($obra, $user, [
        'nome' => 'Memorial DCT-009',
        'status' => 'pendente',
    ]);

    $this->assertDatabaseHas('obra_documentos', ['id' => $documento->id, 'nome' => 'Memorial DCT-009']);

    $this->get(ObraDocumentoResource::getUrl('index'))->assertOk();
    Livewire::test(ListObraDocumentos::class)->assertCanSeeTableRecords([$documento]);

    $documento->update([
        'arquivos_paths' => ['obra-documentos/arquivos/memorial.pdf'],
        'arquivos_nomes' => ['memorial.pdf'],
        'status' => 'enviado',
    ]);

    $this->assertDatabaseHas('obra_documentos', [
        'id' => $documento->id,
        'status' => 'enviado',
    ]);
});
