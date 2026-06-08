<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Matterport extends Model
{
    //
    protected $fillable = [
        'codigo',
        'nome',
        'sigla',
        'nova_sigla',
        'pais_id',
        'estado_id',
        'cidade_id',
        'endereco',
        'link_matterport1',
        'link_matterport2',
        'link_matterport3',
        'link_drone',
        'imagem',
        'documentoPDF',
        'link_google_maps',
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
