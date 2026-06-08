<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empresas extends Model
{
    use HasFactory;

    protected $fillable = [
        'nome',
        'nome_fantasia',
        'responsavel',
        'email',
        'contato',
        'cnpj',
        'tipo',
        'status',
        'cidade_id',
        'estado_id',
        'pais_id',
    ];

    public function cidade()
    {
        return $this->belongsTo(Cidade::class); // Relacionamento com o modelo Cidade
    }

    public function estado()
    {
        return $this->belongsTo(Estado::class); // Relacionamento com Estado
    }

    public function pais()
    {
        return $this->belongsTo(Pais::class); // Relacionamento com País
    }
}
