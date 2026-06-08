<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ColunaPersonalizada extends Model
{
    protected $table = 'colunas_personalizadas';

    protected $fillable = [
        'projeto_id',
        'obra_id',
        'nome',
        'tipo',
        'opcoes',
        'valor',
        'usuario_id',
    ];

    protected $casts = [
        'opcoes' => 'array',
    ];

    public function projeto(): BelongsTo
    {
        return $this->belongsTo(Projeto::class, 'projeto_id');
    }

    public function obra(): BelongsTo
    {
        return $this->belongsTo(Obras::class, 'obra_id');
    }
}
