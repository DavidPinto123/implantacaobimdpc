<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Marca extends Model
{
    protected $fillable = [
        'nome',
    ];

    public function relatoriosVisita()
    {
        return $this->hasMany(RelatorioVisitaTecnica::class);
    }

    public function asEscopos(): BelongsToMany
    {
        return $this->belongsToMany(AsEscopo::class, 'as_escopo_marca')
            ->withTimestamps();
    }
}
