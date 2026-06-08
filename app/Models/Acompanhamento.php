<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Acompanhamento extends Model
{
    use HasFactory;

    protected $table = 'acompanhamentos';

    protected $fillable = [
        'sigla',
        'nova_sigla',
        'nome_mkt',
        'tipo',
        'marca',
        'escopo',
        'pipeline',
        'status',
        'inicio_obra',
        'entrega_obra',
        'implantacao',
        'inauguracao',
        'ano_inauguracao',
        'endereco',
        'cep',
        'bairro',
        'cidade',
        'estado',
        'regiao',
        'pais',
        'razao_social',
        'cnpj',
        'empreendimento_adm',
        'tipo_loja',
        'perfil_loja',
        'tipo_obra',
        'situacao_contratual',
        'data_assinatura_locacao',
        'data_assinatura_distrato',
        'data_encerramento',
        'data_aquisicao',
        'area_contrato',
        'area_util',
        'area_producao',
        'estacionamento',
        'bicicletario',
        'ginastica',
        'spa',
        'smartbike',
        'strong',
        'smartcross',
        'smartbox',
        'smartshape',
        'race',
        'vidya',
        'jabhouse',
        'tonus_gym',
        'one_pilates',
        'velocity',
        'kore',
        'burn',
        'squad',
        'skill_mill',
        'torq',
        'obs',
        'inicio_projeto',
    ];
}
