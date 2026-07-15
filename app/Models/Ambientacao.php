<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ambientacao extends Model
{
    protected $table = 'ambientacoes';

    protected $fillable = [
        'codigo',
        'nome',
        'sigla',
        'nova_sigla',
        'pais_id',
        'estado_id',
        'cidade_id',
        'pavimento',
        'ambiente',
        'link_render',
    ];

    public function pais()
    {
        return $this->belongsTo(Pais::class);
    }

    public function estado()
    {
        return $this->belongsTo(Estado::class);
    }

    public function cidade()
    {
        return $this->belongsTo(Cidade::class);
    }
}
