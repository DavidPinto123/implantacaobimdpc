<?php

use App\Filament\Resources\Asas\AsaResource;

it('smoke de navegacao do resource ASA', function () {
    $user = createResourceBaselineUser([
        'ViewAny:Asa',
        'View:Asa',
        'Create:Asa',
        'Update:Asa',
    ]);

    $this->actingAs($user);

    $asa = createAsaRecord($user, ['descricao' => 'ASA Browser DCT-009']);

    $indexUrl = AsaResource::getUrl('index');
    $editUrl = AsaResource::getUrl('edit', ['record' => $asa]);

    visit($indexUrl)
        ->assertPathIs(parse_url($indexUrl, PHP_URL_PATH))
        ->assertSee('CC - CONTROLE DE CUSTOS ADICIONAIS');

    // visit($createUrl)
    //     ->assertPathIs(parse_url($createUrl, PHP_URL_PATH))
    //     ->assertSee('AUTORIZAÇÃO DE SERVIÇO ADICIONAL');

    visit($editUrl)
        ->assertPathIs(parse_url($editUrl, PHP_URL_PATH))
        ->assertSee('ASA Browser DCT-009')
        ->assertSee('Valores');
});
