<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Departamentos extends Model
{
    protected $table = 'departamentos';

    // Defina os campos que podem ser preenchidos em massa
    protected $fillable = [
        'nova_sigla',
        'unidade',
        'departamento',
        'area',
        'data_extracao',
    ];
}
