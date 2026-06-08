<?php

use App\Filament\Resources\AsFaixaAreas\AsFaixaAreaResource;

it('smoke de navegacao do resource AsFaixaArea', function () {
    $user = createResourceBaselineUser([
        'ViewAny:AsFaixaArea',
        'View:AsFaixaArea',
        'Create:AsFaixaArea',
        'Update:AsFaixaArea',
    ]);

    $this->actingAs($user);

    $faixa = createAsFaixaAreaRecord(['nome' => 'Faixa Browser DCT-009']);

    $indexUrl = AsFaixaAreaResource::getUrl('index');
    $createUrl = AsFaixaAreaResource::getUrl('create');
    $editUrl = AsFaixaAreaResource::getUrl('edit', ['record' => $faixa]);

    visit($indexUrl)
        ->assertPathIs(parse_url($indexUrl, PHP_URL_PATH))
        ->assertSee('Cadastro de Faixas');

    visit($createUrl)
        ->assertPathIs(parse_url($createUrl, PHP_URL_PATH))
        ->assertSee('Nome da Faixa');

    visit($editUrl)
        ->assertPathIs(parse_url($editUrl, PHP_URL_PATH))
        ->assertSee('Faixa Browser DCT-009');
});
