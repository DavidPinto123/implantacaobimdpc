<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AmbienteRdc50 extends Model
{
    protected $table = 'ambientes_rdc50';

    protected $fillable = [
        'unidade_funcional',
        'subgrupo',
        'tipo',
        'circulacao',
        'num_atividade',
        'ambiente',
        'nome_fiorentini',
        'obrigatoriedade',
        'quantificacao_minima',
        'pe_direito_minimo',
        'area_dimensao_minima',
        'instalacoes',
        'rev_piso',
        'rev_parede',
        'rev_forro',
        'rev_rodape',
        'rev_rodameio',
        'comentarios',
    ];
}
