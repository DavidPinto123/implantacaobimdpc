<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ambientes extends Model
{
    protected $table = 'ambientes';

    // Defina os campos que podem ser preenchidos em massa
    protected $fillable = [
        'nova_sigla',
        'unidade',
        'marca',
        'departamento',
        'ambiente',
        'area',
        'pavimento',
        'data_extracao',
    ];
}
