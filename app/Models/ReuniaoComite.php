<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReuniaoComite extends Model
{
    use HasFactory;

    protected $fillable = [
        'projeto_id',
        'estado_id',
        'unidade',
        'status_reuniao_comite',
        'relatorio_visita',
        'estudo_massa',
        'levantamento_cadastral',
    ];

    // Relacionamento com Projeto
    public function projeto()
    {
        return $this->belongsTo(Projeto::class);
    }

    // Relacionamento com Estado
    public function estado()
    {
        return $this->belongsTo(Estado::class);
    }
}
