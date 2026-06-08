<?php

use App\Filament\Resources\AsFaixaAreas\AsFaixaAreaResource;
use App\Filament\Resources\AsFaixaAreas\Pages\ListAsFaixaAreas;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

beforeEach(function () {
    setupAdminPanelForTests();
});

it('executa CRUD basico de faixas de area', function () {
    $user = createResourceBaselineUser([
        'ViewAny:AsFaixaArea',
        'View:AsFaixaArea',
        'Create:AsFaixaArea',
        'Update:AsFaixaArea',
        'Delete:AsFaixaArea',
    ]);

    $this->actingAs($user);

    $faixa = createAsFaixaAreaRecord(['nome' => 'Faixa DCT-009']);

    $this->assertDatabaseHas('as_faixa_areas', ['id' => $faixa->id, 'nome' => 'Faixa DCT-009']);

    $this->get(AsFaixaAreaResource::getUrl('index'))->assertOk();
    Livewire::test(ListAsFaixaAreas::class)->assertCanSeeTableRecords([$faixa]);

    $faixa->update(['area_max' => 250]);
    $this->assertDatabaseHas('as_faixa_areas', ['id' => $faixa->id, 'area_max' => 250]);

    $faixa->delete();
    $this->assertDatabaseMissing('as_faixa_areas', ['id' => $faixa->id]);
});
