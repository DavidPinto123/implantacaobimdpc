<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsFaixaArea extends Model
{
    protected $fillable = [
        'nome',
        'area_min',
        'area_max',
    ];

    public function escopos()
    {
        return $this->belongsToMany(
            AsEscopo::class,
            'as_escopo_faixa_area',
            'as_faixa_area_id',
            'as_escopo_id'
        )->withPivot('valor_m2')->withTimestamps();
    }
}
