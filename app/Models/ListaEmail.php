<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ListaEmail extends Model
{
    protected $fillable = [
        'nome',
        'descricao',
        'emails',
        'ativo',
    ];

    protected $casts = [
        'emails' => 'array',
        'ativo' => 'boolean',
    ];
}
