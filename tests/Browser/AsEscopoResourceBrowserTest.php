<?php

use App\Filament\Resources\AsEscopos\AsEscopoResource;

it('smoke de navegacao do resource AsEscopo', function () {
    $user = createResourceBaselineUser([
        'ViewAny:AsEscopo',
        'View:AsEscopo',
        'Create:AsEscopo',
        'Update:AsEscopo',
    ]);

    $this->actingAs($user);

    $escopo = createAsEscopoRecord(['escopo' => 'Escopo Browser DCT-009']);

    $indexUrl = AsEscopoResource::getUrl('index');
    $createUrl = AsEscopoResource::getUrl('create');
    $editUrl = AsEscopoResource::getUrl('edit', ['record' => $escopo]);

    visit($indexUrl)
        ->assertPathIs(parse_url($indexUrl, PHP_URL_PATH))
        ->assertSee('Escopos de A.S.');

    visit($createUrl)
        ->assertPathIs(parse_url($createUrl, PHP_URL_PATH))
        ->assertSee('Cadastro de Escopo de A.S.');

    visit($editUrl)
        ->assertPathIs(parse_url($editUrl, PHP_URL_PATH))
        ->assertSee('Cadastro de Escopo de A.S.');
});
