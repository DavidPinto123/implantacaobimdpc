<?php

use App\Filament\Resources\AsEscopos\AsEscopoResource;
use App\Filament\Resources\AsEscopos\Pages\ListAsEscopos;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

beforeEach(function () {
    setupAdminPanelForTests();
});

it('executa CRUD basico de escopos de AS', function () {
    $user = createResourceBaselineUser([
        'ViewAny:AsEscopo',
        'View:AsEscopo',
        'Create:AsEscopo',
        'Update:AsEscopo',
        'Delete:AsEscopo',
    ]);

    $this->actingAs($user);

    $escopo = createAsEscopoRecord(['escopo' => 'Escopo DCT-009']);

    $this->assertDatabaseHas('as_escopos', ['id' => $escopo->id, 'escopo' => 'Escopo DCT-009']);

    $this->get(AsEscopoResource::getUrl('index'))->assertOk();
    Livewire::test(ListAsEscopos::class)->assertCanSeeTableRecords([$escopo]);

    $escopo->update(['is_active' => false]);
    $this->assertDatabaseHas('as_escopos', ['id' => $escopo->id, 'is_active' => 0]);

    $escopo->delete();
    $this->assertDatabaseMissing('as_escopos', ['id' => $escopo->id]);
});

it('salva percentuais padrao no cadastro de escopo', function () {
    $user = createResourceBaselineUser([
        'ViewAny:AsEscopo',
        'View:AsEscopo',
        'Create:AsEscopo',
        'Update:AsEscopo',
    ]);

    $this->actingAs($user);

    $escopo = createAsEscopoRecord([
        'percentual_faturamento_mao_obra_default' => 72.5,
        'percentual_faturamento_material_default' => 27.5,
    ]);

    $this->assertDatabaseHas('as_escopos', [
        'id' => $escopo->id,
        'percentual_faturamento_mao_obra_default' => 72.5,
        'percentual_faturamento_material_default' => 27.5,
    ]);
});

it('normaliza percentuais padrao do escopo para totalizar 100 ao salvar', function () {
    $escopo = createAsEscopoRecord([
        'percentual_faturamento_mao_obra_default' => 82.25,
        'percentual_faturamento_material_default' => 10,
    ]);

    expect($escopo->refresh()->percentual_faturamento_mao_obra_default)->toBe('82.25')
        ->and($escopo->percentual_faturamento_material_default)->toBe('17.75');
});
