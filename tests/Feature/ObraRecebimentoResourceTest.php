<?php

use App\Filament\Resources\ObraRecebimentos\ObraRecebimentoResource;
use App\Filament\Resources\ObraRecebimentos\Pages\ListObraRecebimentos;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

beforeEach(function () {
    setupAdminPanelForTests();
});

it('executa CRUD basico para controle de recebimentos', function () {
    $user = createResourceBaselineUser([
        'ViewAny:ObraRecebimento',
        'View:ObraRecebimento',
        'Create:ObraRecebimento',
        'Update:ObraRecebimento',
        'Delete:ObraRecebimento',
    ]);

    setGestorObras($user);

    $this->actingAs($user);

    $obra = createObraRecord($user);
    $construtora = createConstrutoraRecord();
    attachObraConstrutora($obra, $construtora);

    $recebimento = createObraRecebimentoRecord($obra, $construtora, $user, ['nome' => 'Material DCT-009']);

    $this->assertDatabaseHas('obra_recebimentos', ['id' => $recebimento->id, 'nome' => 'Material DCT-009']);

    $this->get(ObraRecebimentoResource::getUrl('index'))->assertOk();
    Livewire::test(ListObraRecebimentos::class)->assertCanSeeTableRecords([$recebimento]);

    $recebimento->update([
        'foto_entrega_paths' => ['obra-recebimentos/fotos/foto.jpg'],
        'foto_entrega_nomes' => ['foto.jpg'],
        'nota_fiscal_paths' => ['obra-recebimentos/notas-fiscais/nf.pdf'],
        'nota_fiscal_nomes' => ['nf.pdf'],
        'status' => 'recebido',
    ]);

    $this->assertDatabaseHas('obra_recebimentos', ['id' => $recebimento->id, 'status' => 'recebido']);

    $recebimento->delete();
    $this->assertDatabaseMissing('obra_recebimentos', ['id' => $recebimento->id]);
});
