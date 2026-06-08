<?php

it('redireciona a URL raiz para o caminho admin do Filament', function () {
    $this->get('/')
        ->assertRedirect('/admin');
});
