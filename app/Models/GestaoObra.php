<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GestaoObra extends Model
{
    protected $fillable = [
        'codigo',
        'nome',
        'construtora',
        'orcamento_inicial',
        'realizado',
        'comprometido',
        'pdp',
    ];

    protected $appends = ['saldo'];

    public function construtora()
    {
        return $this->belongsTo(Construtora::class);
    }

    public function getSaldoAttribute()
    {
        return $this->orcamento_inicial - ($this->realizado + $this->comprometido);
    }
}
