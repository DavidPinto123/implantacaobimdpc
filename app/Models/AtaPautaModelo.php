<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AtaPautaModelo extends Model
{
    protected $table = 'ata_pauta_modelos';

    protected $fillable = ['titulo', 'uso'];
}
