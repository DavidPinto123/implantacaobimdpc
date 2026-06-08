<?php

namespace App\Models;

use App\Enums\PosObra\TipoConstrutora;
use App\Models\PosObra\DisciplinaConfig;
use App\Models\PosObra\Pendencia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Construtora extends Model
{
    protected $fillable = [
        'nome',
        'cnpj',
        'inscricao_estadual',
        'telefone',
        'email',
        'endereco',
        'cep',
        'responsavel',
        'tipo',
        'telefone_whatsapp',
    ];

    protected $casts = [
        'tipo' => TipoConstrutora::class,
    ];
    /*
    public function obras()
    {
        return $this->hasMany(GestaoObra::class);
    }
    */

    public function obras()
    {
        return $this->belongsToMany(
            Obras::class,
            'obra_construtora',
            'construtora_id',
            'obra_id'
        );
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'construtoras_id');
    }

    public function pendencias(): HasMany
    {
        return $this->hasMany(Pendencia::class, 'construtora_id');
    }

    public function disciplinas()
    {
        return $this->belongsToMany(
            DisciplinaConfig::class,
            'construtora_disciplina',
            'construtora_id',
            'disciplina_config_id',
        );
    }

    public function recebimentos(): HasMany
    {
        return $this->hasMany(ObraRecebimento::class, 'construtora_id');
    }
}
