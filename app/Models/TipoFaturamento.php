<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoFaturamento extends Model
{
    protected $fillable = ['nome'];

    public function notasFiscais()
    {
        return $this->belongsToMany(NotaFiscal::class, 'nota_fiscal_tipo_faturamento');
    }
}
