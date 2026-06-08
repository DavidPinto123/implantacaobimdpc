<?php

use App\Models\Cidade;
use App\Models\Estado;
use App\Models\Etapa;
use App\Models\Pais;
use App\Models\Projeto;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

function createProjetoPorEstadoDependencies(): array
{
    $pais = Pais::create(['nome' => 'Brasil']);
    $estado = Estado::create(['nome' => 'São Paulo', 'uf' => 'SP', 'pais_id' => $pais->id]);
    $cidade = Cidade::create(['nome' => 'São Paulo', 'estado_id' => $estado->id]);
    $etapaProspeccao = Etapa::create(['nome' => 'Prospecção']);

    return compact('pais', 'estado', 'cidade', 'etapaProspeccao');
}

function createProjetoForEstadoBuckets(User $user, array $dependencies, array $overrides = []): Projeto
{
    return Projeto::create(array_merge([
        'nome' => 'Projeto por Estado',
        'sigla' => 'PES',
        'status' => 'Em processo',
        'user_id' => $user->id,
        'etapa_id' => $dependencies['etapaProspeccao']->id,
        'cidade_id' => $dependencies['cidade']->id,
        'estado_id' => $dependencies['estado']->id,
        'pais_id' => $dependencies['pais']->id,
        'rua' => 'Rua Teste',
        'bairro' => 'Centro',
        'cep' => '01000-000',
        'numero' => '10',
    ], $overrides));
}

it('retorna JSON agrupado com registros nos agrupamentos esperados para um estado válido', function () {
    $user = User::factory()->active()->create();
    $this->actingAs($user);
    $deps = createProjetoPorEstadoDependencies();

    $projetoProspeccao = createProjetoForEstadoBuckets($user, $deps, [
        'nome' => 'Projeto Prospecção',
        'status' => 'Stand-by',
    ]);
    $projetoProspeccao->etapas()->sync([$deps['etapaProspeccao']->id]);

    $projetoAssinatura = createProjetoForEstadoBuckets($user, $deps, [
        'nome' => 'Projeto Assinado',
        'sigla' => 'ASS',
        'status' => 'Cancelada',
        'status_contrato' => 'ASSINADO',
        'numero' => '11',
    ]);

    $projetoEmProcesso = createProjetoForEstadoBuckets($user, $deps, [
        'nome' => 'Projeto Em Processo',
        'sigla' => 'EMP',
        'status' => 'Em processo',
        'numero' => '12',
    ]);

    $projetoObra = createProjetoForEstadoBuckets($user, $deps, [
        'nome' => 'Projeto em Obra',
        'sigla' => 'OBR',
        'status' => 'Obras',
        'numero' => '13',
    ]);

    $response = $this->getJson('/projetos-por-estado/SP');

    $response
        ->assertOk()
        ->assertJsonCount(1, 'prospeccao')
        ->assertJsonCount(1, 'assinatura')
        ->assertJsonCount(1, 'projetos')
        ->assertJsonCount(1, 'obra')
        ->assertJsonPath('prospeccao.0.id', $projetoProspeccao->id)
        ->assertJsonPath('assinatura.0.id', $projetoAssinatura->id)
        ->assertJsonPath('projetos.0.id', $projetoEmProcesso->id)
        ->assertJsonPath('obra.0.id', $projetoObra->id);
});

it('retorna arrays vazios para uma sigla de estado desconhecida', function () {
    $this->getJson('/projetos-por-estado/XX')
        ->assertOk()
        ->assertExactJson([
            'prospeccao' => [],
            'assinatura' => [],
            'projetos' => [],
            'obra' => [],
        ]);
});
