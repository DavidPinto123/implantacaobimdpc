<?php

use App\Enums\AsStatus;
use App\Models\Asa;
use App\Models\AsEscopo;
use App\Models\AsFaixaArea;
use App\Models\Construtora;
use App\Models\ObraDocumento;
use App\Models\ObraRecebimento;
use App\Models\Obras;
use App\Models\Projeto;
use App\Models\Setor;
use App\Models\User;

function createResourceBaselineUser(array $permissions): User
{
    ensureDefaultRoles();

    return createActiveUserWithPermissions($permissions);
}

function createResourceProjeto(User $user, array $overrides = []): Projeto
{
    return createProjetoRecord($user, $overrides);
}

function createObraRecord(User $user, array $overrides = []): Obras
{
    $projeto = createResourceProjeto($user);

    return Obras::create(array_merge([
        'projeto_id' => $projeto->id,
        'codigo' => 'OBR-'.str()->upper(str()->random(6)),
        'unidade' => $projeto->nome,
        'status' => 'Em processo',
        'homologados_em_atraso' => 'nao',
        'relatorio_fotografico' => 'nao_enviado',
        'termo_de_posse' => 'nao',
        'status_visita' => 'NÃO SOLICITADO',
        'status_proj_exec' => 'NÃO SOLICITADO',
    ], $overrides));
}

function createConstrutoraRecord(array $overrides = []): Construtora
{
    return Construtora::create(array_merge([
        'nome' => 'Fornecedor Teste '.str()->upper(str()->random(4)),
        'tipo' => 'CONSTRUTORA',
        'cnpj' => '00.000.000/0001-91',
        'telefone' => '(11) 99999-0000',
        'email' => 'construtora+'.str()->lower(str()->random(5)).'@teste.local',
    ], $overrides));
}

function attachObraConstrutora(Obras $obra, Construtora $construtora): void
{
    $obra->construtoras()->syncWithoutDetaching([$construtora->id]);
}

function createObraDocumentoRecord(Obras $obra, ?User $usuario = null, array $overrides = []): ObraDocumento
{
    return ObraDocumento::create(array_merge([
        'obra_id' => $obra->id,
        'nome' => 'Documento '.str()->upper(str()->random(4)),
        'status' => 'pendente',
        'usuario_id' => $usuario?->id,
    ], $overrides));
}

function createObraRecebimentoRecord(Obras $obra, ?Construtora $construtora = null, ?User $usuario = null, array $overrides = []): ObraRecebimento
{
    return ObraRecebimento::create(array_merge([
        'obra_id' => $obra->id,
        'construtora_id' => $construtora?->id,
        'nome' => 'Item '.str()->upper(str()->random(4)),
        'status' => 'pendente',
        'usuario_id' => $usuario?->id,
    ], $overrides));
}

function createAsaRecord(User $user, array $overrides = []): Asa
{
    $projeto = createResourceProjeto($user);

    return Asa::create(array_merge([
        'numero_asa' => 'ASA-'.str()->upper(str()->random(8)),
        'projeto_id' => $projeto->id,
        'sigla' => $projeto->sigla,
        'endereco' => $projeto->endereco,
        'status' => AsStatus::SOLICITADO,
        'objeto' => 'Objeto ASA teste',
        'descricao' => 'Descrição ASA teste',
        'altera_prazo' => 'Não',
        'valor_bruto' => 1000,
        'desconto' => 100,
        'valor_total' => 900,
        'gestor_id' => $user->id,
        'solicitante' => 'Fornecedor Teste',
    ], $overrides));
}

function createAsEscopoRecord(array $overrides = []): AsEscopo
{
    return AsEscopo::create(array_merge([
        'grupo' => 'Civil',
        'numero_as' => 'AS-'.str()->upper(str()->random(6)),
        'escopo' => 'Escopo teste '.str()->upper(str()->random(5)),
        'is_active' => true,
    ], $overrides));
}

function createAsFaixaAreaRecord(array $overrides = []): AsFaixaArea
{
    return AsFaixaArea::create(array_merge([
        'nome' => 'Faixa '.str()->upper(str()->random(4)),
        'area_min' => 100,
        'area_max' => 200,
    ], $overrides));
}

function setGestorObras(User $user): void
{
    $user->assignRole('Gestor');

    $setorObras = Setor::firstOrCreate(['setor' => 'Obras']);
    $user->setores()->syncWithoutDetaching([$setorObras->id]);
}
