<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdemInvestimento extends Model
{
    protected $fillable = [
        'projeto_id',
        'valor_total',
        'area',
        'custo_m2',
        'estrutura',
        'pdf_path',
        'user_id',
    ];

    protected $casts = [
        'estrutura' => 'array',
        'valor_total' => 'float',
        'area' => 'float',
        'custo_m2' => 'float',
    ];

    public function projeto()
    {
        return $this->belongsTo(Projeto::class);
    }
}
