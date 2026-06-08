<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Etapa extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'nome',
    ];

    /*
    public function projetos():HasMany{
    	return $this->hasMany(Projeto::class);
    }
    */
    public function projetos()
    {
        return $this->belongsToMany(Projeto::class, 'etapa_projeto');
    }
}
