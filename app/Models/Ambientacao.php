<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ambientacao extends Model
{
    protected $table = 'ambientacoes';

    protected $fillable = [
        'codigo',
        'nome',
        'sigla',
        'nova_sigla',
        'bloco_torre',
        'departamento',
        'pais_id',
        'estado_id',
        'cidade_id',
        'pavimento',
        'ambiente',
        'link_render',
        'pano_equirretangular',
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

    public function imagens(): HasMany
    {
        return $this->hasMany(AmbientacaoImagem::class);
    }
}
