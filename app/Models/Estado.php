<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estado extends Model
{
    use HasFactory;

    protected $fillable = [
        'pais_id',
        'nome',
        'uf',
        'iso_3166_2',
    ];

    public function pais()
    {
        return $this->belongsTo(Pais::class);
    }

    public function projetos()
    {
        return $this->hasMany(Projeto::class);
    }
}
