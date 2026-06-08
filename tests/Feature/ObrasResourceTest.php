<?php

use App\Filament\Resources\Obras\ObrasResource;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

beforeEach(function () {
    setupAdminPanelForTests();
});

it('executa CRUD basico de Obras via fallback de modelo', function () {
    $user = createResourceBaselineUser([
        'ViewAny:Obras',
        'View:Obras',
        'Create:Obras',
        'Update:Obras',
        'Delete:Obras',
    ]);

    $this->actingAs($user);

    $obra = createObraRecord($user, ['codigo' => 'OBR-DCT009']);

    $this->assertDatabaseHas('obras', ['id' => $obra->id, 'codigo' => 'OBR-DCT009']);

    $this->get(ObrasResource::getUrl('index'))->assertOk();
    $this->get(ObrasResource::getUrl('view', ['record' => $obra]))->assertOk();
    $this->get(ObrasResource::getUrl('edit', ['record' => $obra]))->assertOk();

    $obra->update(['status' => 'Obras']);
    $this->assertDatabaseHas('obras', ['id' => $obra->id, 'status' => 'Obras']);

    $obra->delete();
    $this->assertSoftDeleted('obras', ['id' => $obra->id]);
});
