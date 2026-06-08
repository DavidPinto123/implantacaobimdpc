<?php

use App\Filament\Resources\ObraDocumentos\ObraDocumentoResource;

it('smoke de navegacao do resource ObraDocumento (list/edit)', function () {
    $user = createResourceBaselineUser([
        'ViewAny:AutorizacaoServico',
        'View:AutorizacaoServico',
        'Update:AutorizacaoServico',
    ]);

    setGestorObras($user);
    $this->actingAs($user);

    $obra = createObraRecord($user);
    $documento = createObraDocumentoRecord($obra, $user, ['nome' => 'Documento Browser DCT-009']);

    $indexUrl = ObraDocumentoResource::getUrl('index');
    $editUrl = ObraDocumentoResource::getUrl('edit', ['record' => $documento]);

    visit($indexUrl)
        ->assertPathIs(parse_url($indexUrl, PHP_URL_PATH))
        ->assertSee('Documentos de Obra');

    visit($editUrl)
        ->assertPathIs(parse_url($editUrl, PHP_URL_PATH))
        ->assertSee('Envio de documentos')
        ->assertSee('Uploads');
});
