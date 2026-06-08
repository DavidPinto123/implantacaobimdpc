<?php

namespace App\Models\PosObra;

use App\Models\Construtora;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DisciplinaConfig extends Model
{
    protected $table = 'po_disciplinas_config';

    protected $fillable = [
        'codigo',
        'label',
        'ativo',
        'ordem',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'ordem' => 'integer',
    ];

    public function pendencias(): HasMany
    {
        return $this->hasMany(Pendencia::class, 'disciplina_config_id');
    }

    public function construtoras()
    {
        return $this->belongsToMany(
            Construtora::class,
            'construtora_disciplina',
            'disciplina_config_id',
            'construtora_id',
        );
    }
}
