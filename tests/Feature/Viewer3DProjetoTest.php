<?php

use App\Models\Cidade;
use App\Models\Estado;
use App\Models\Etapa;
use App\Models\Pais;
use App\Models\Projeto;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

function createProjetoForViewer(User $user, array $overrides = []): Projeto
{
    $pais = Pais::create(['nome' => 'Brasil']);
    $estado = Estado::create(['nome' => 'São Paulo', 'uf' => 'SP', 'pais_id' => $pais->id]);
    $cidade = Cidade::create(['nome' => 'São Paulo', 'estado_id' => $estado->id]);
    $etapa = Etapa::create(['nome' => 'Prospecção']);

    return Projeto::create(array_merge([
        'nome' => 'Projeto Viewer',
        'sigla' => 'PVW',
        'nova_sigla' => 'PVW-01',
        'status' => 'Em processo',
        'user_id' => $user->id,
        'etapa_id' => $etapa->id,
        'cidade_id' => $cidade->id,
        'estado_id' => $estado->id,
        'pais_id' => $pais->id,
        'rua' => 'Rua Teste',
        'bairro' => 'Centro',
        'cep' => '01000-000',
        'numero' => '300',
        'link_docs' => null,
    ], $overrides));
}

it('usuário autenticado consegue abrir a rota do viewer com sucesso', function () {
    $user = User::factory()->active()->create();
    $this->actingAs($user);
    $projeto = createProjetoForViewer($user);

    $this->get(route('filament.pages.viewer3d-projeto', ['projeto' => $projeto]))
        ->assertOk();
});

it('rota do viewer mantém contexto correto do projeto sem dependência de api externa', function () {
    $user = User::factory()->active()->create();
    $this->actingAs($user);
    $projeto = createProjetoForViewer($user, ['nome' => 'Projeto Contexto Viewer']);

    $viewerUrl = route('filament.pages.viewer3d-projeto', ['projeto' => $projeto]);

    expect($viewerUrl)->toContain((string) $projeto->id);

    $this->get($viewerUrl)
        ->assertOk()
        ->assertSee('forgeViewer', false)
        ->assertSee('viewer3D.js', false);
});
