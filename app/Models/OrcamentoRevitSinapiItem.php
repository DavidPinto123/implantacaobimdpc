<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrcamentoRevitSinapiItem extends Model
{
    protected $connection = 'revit';

    protected $table = 'orcamento_revit_sinapi_itens';

    public $timestamps = false;

    protected $casts = [
        'quantidade'     => 'decimal:3',
        'valor_unitario' => 'decimal:2',
        'data_emissao'   => 'date',
        'atualizado_em'  => 'datetime',
    ];
}
