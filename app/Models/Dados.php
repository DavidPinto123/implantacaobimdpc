<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dados extends Model
{
    protected $table = 'dados';

    // Define os campos que podem ser preenchidos automaticamente
    protected $fillable = [
        'nova_sigla',
        'unidade',
        'marca',
        'bloco_tipo',
        'categoria',
        'descricao',
        'quantidade',
        'un',
        'pavimento',
        'status',
    ];
}
