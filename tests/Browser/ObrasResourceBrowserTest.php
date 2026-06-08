<?php

use App\Filament\Resources\Obras\ObrasResource;

it('smoke de navegacao do resource Obras', function () {
    $user = createResourceBaselineUser([
        'ViewAny:Obras',
        'View:Obras',
        'Create:Obras',
        'Update:Obras',
    ]);

    $this->actingAs($user);

    $obra = createObraRecord($user, ['codigo' => 'OBR-BROWSER']);

    $indexUrl = ObrasResource::getUrl('index');
    $createUrl = ObrasResource::getUrl('create');
    $viewUrl = ObrasResource::getUrl('view', ['record' => $obra]);
    $editUrl = ObrasResource::getUrl('edit', ['record' => $obra]);

    visit($indexUrl)
        ->assertPathIs(parse_url($indexUrl, PHP_URL_PATH));

    visit($createUrl)
        ->assertPathIs(parse_url($createUrl, PHP_URL_PATH))
        ->assertSee('Dados da Obra PIPE');

    visit($viewUrl)
        ->assertPathIs(parse_url($viewUrl, PHP_URL_PATH))
        ->assertSee('OBR-BROWSER');

    visit($editUrl)
        ->assertPathIs(parse_url($editUrl, PHP_URL_PATH))
        ->assertSee('Informações do Projeto');
});
