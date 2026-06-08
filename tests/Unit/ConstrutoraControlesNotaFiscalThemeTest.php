<?php

it('mantem o painel do filtro de unidades sem recortar o dropdown', function (): void {
    $themeCss = file_get_contents(__DIR__.'/../../resources/css/filament/admin/theme.css');

    expect($themeCss)
        ->toContain('.cnf-fornecedor-panel')
        ->toContain('overflow: visible;')
        ->toContain('z-index: 20;');
});
