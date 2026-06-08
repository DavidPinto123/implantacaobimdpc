<?php

beforeEach(function () {
    ensureDefaultRoles();
});

it('acessa a lista de projetos em contexto admin autenticado e visualiza elementos principais da interface', function () {
    $user = createActiveUserWithPermissions(['ViewAny:Projeto']);

    $this->actingAs($user);

    createProjetoRecord($user, ['nome' => 'Projeto Lista Browser']);

    visit('/admin/projetos')
        ->assertPathIs('/admin/projetos')
        ->assertSee('Projetos')
        ->assertSee('Exportar Projetos')
        ->assertPresent('button[type="button"]');
});

it('cobre smoke de navegação no browser para páginas de criar, visualizar e editar', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:Projeto',
        'View:Projeto',
        'Create:Projeto',
        'Update:Projeto',
        'Delete:Projeto',
    ]);

    $this->actingAs($user);

    visit('/admin/projetos/create')
        ->assertPathIs('/admin/projetos/create')
        ->assertSee('Informações do Projeto')
        ->assertPresent('button[type="submit"]');

    $projeto = createProjetoRecord($user, ['nome' => 'Projeto CRUD Browser']);

    visit("/admin/projetos/{$projeto->id}")
        ->assertPathIs("/admin/projetos/{$projeto->id}")
        ->assertSee('Projeto CRUD Browser');

    visit("/admin/projetos/{$projeto->id}/edit")
        ->assertPathIs("/admin/projetos/{$projeto->id}/edit")
        ->assertSee('Projeto CRUD Browser')
        ->assertSee('Visualizar')
        ->assertSee('Excluir');
});
