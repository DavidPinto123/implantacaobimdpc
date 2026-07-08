<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ata extends Model
{
    protected $table = 'atas';

    protected $fillable = [
        'projeto_id',
        'titulo',
        'data_reuniao',
        'hora_inicio',
        'hora_fim',
        'local',
        'resumo',
        'link_youtube',
        'criado_por',
    ];

    protected $casts = [
        'data_reuniao' => 'date',
    ];

    public function projeto(): BelongsTo
    {
        return $this->belongsTo(Projeto::class);
    }

    public function criador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'criado_por');
    }

    public function participantes(): HasMany
    {
        return $this->hasMany(AtaParticipante::class);
    }

    public function temas(): HasMany
    {
        return $this->hasMany(AtaTema::class)->orderBy('ordem');
    }

    public function anexos(): HasMany
    {
        return $this->hasMany(AtaAnexo::class)->orderBy('ordem');
    }
}
