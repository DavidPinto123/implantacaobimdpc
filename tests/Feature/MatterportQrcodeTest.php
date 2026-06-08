<?php

use App\Models\Cidade;
use App\Models\Estado;
use App\Models\Etapa;
use App\Models\Pais;
use App\Models\Projeto;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

function createProjetoForMatterport(array $overrides = []): Projeto
{
    $user = User::factory()->active()->create();
    $pais = Pais::create(['nome' => 'Brasil']);
    $estado = Estado::create(['nome' => 'São Paulo', 'uf' => 'SP', 'pais_id' => $pais->id]);
    $cidade = Cidade::create(['nome' => 'São Paulo', 'estado_id' => $estado->id]);
    $etapa = Etapa::create(['nome' => 'Prospecção']);

    return Projeto::create(array_merge([
        'nome' => 'Projeto QR Matterport',
        'sigla' => 'PQM',
        'status' => 'Em processo',
        'user_id' => $user->id,
        'etapa_id' => $etapa->id,
        'cidade_id' => $cidade->id,
        'estado_id' => $estado->id,
        'pais_id' => $pais->id,
        'rua' => 'Rua Teste',
        'bairro' => 'Centro',
        'cep' => '01000-000',
        'numero' => '200',
        'link_matterport' => 'https://matterport.com/show/?m=xyz987',
    ], $overrides));
}

it('retorna imagem png para projeto com link_matterport', function () {
    $this->actingAs(User::factory()->active()->create());
    $projeto = createProjetoForMatterport();

    $this->get(route('projetos.matterport.qrcode', ['projeto' => $projeto]))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png');
});

it('retorna 404 para projeto sem link_matterport', function () {
    $this->actingAs(User::factory()->active()->create());
    $projeto = createProjetoForMatterport(['link_matterport' => null]);

    $this->get(route('projetos.matterport.qrcode', ['projeto' => $projeto]))
        ->assertNotFound();
});
