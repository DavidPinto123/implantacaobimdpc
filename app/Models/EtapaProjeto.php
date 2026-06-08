<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EtapaProjeto extends Model
{
    protected $table = 'etapa_projeto';

    protected $fillable = ['projeto_id', 'etapa_id'];

    public $timestamps = true; // se não tiver created_at/updated_at

    public function etapa()
    {
        return $this->belongsTo(Etapa::class, 'etapa_id');
    }
}
