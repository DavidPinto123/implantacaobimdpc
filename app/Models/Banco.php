<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banco extends Model
{
    protected $fillable = [
        'codigo',
        'ispb',
        'nome_reduzido',
        'nome_extenso',
        'participa_compe',
        'ativo',
        'sincronizado_em',
    ];

    protected function casts(): array
    {
        return [
            'participa_compe' => 'boolean',
            'ativo' => 'boolean',
            'sincronizado_em' => 'datetime',
        ];
    }
}
