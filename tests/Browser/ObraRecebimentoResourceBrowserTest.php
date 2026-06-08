<?php

use App\Filament\Resources\ObraRecebimentos\ObraRecebimentoResource;

it('smoke de navegacao do resource ObraRecebimento (list/edit)', function () {
    $user = createResourceBaselineUser([
        'ViewAny:ObraRecebimento',
        'View:ObraRecebimento',
        'Update:ObraRecebimento',
    ]);

    setGestorObras($user);
    $this->actingAs($user);

    $obra = createObraRecord($user);
    $construtora = createConstrutoraRecord();
    attachObraConstrutora($obra, $construtora);
    $recebimento = createObraRecebimentoRecord($obra, $construtora, $user, ['nome' => 'Recebimento Browser DCT-009']);

    $indexUrl = ObraRecebimentoResource::getUrl('index');
    $editUrl = ObraRecebimentoResource::getUrl('edit', ['record' => $recebimento]);

    visit($indexUrl)
        ->assertPathIs(parse_url($indexUrl, PHP_URL_PATH))
        ->assertSee('Controle de Recebimentos');

    visit($editUrl)
        ->assertPathIs(parse_url($editUrl, PHP_URL_PATH))
        ->assertSee('Entrega de materiais')
        ->assertSee('Comprovantes');
});
