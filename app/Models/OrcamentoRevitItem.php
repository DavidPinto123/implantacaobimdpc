<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrcamentoRevitItem extends Model
{
    protected $connection = 'revit';

    protected $table = 'orcamento_revit_itens';

    public $timestamps = false;

    protected $casts = [
        'quantidade' => 'decimal:3',
        'valor_mat' => 'decimal:2',
        'valor_mo' => 'decimal:2',
        'atualizado_em' => 'datetime',
    ];
}
