<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportacaoTemplate extends Model
{
    protected $table = 'importacao_templates';

    protected $fillable = [
        'nome',
        'modulo',
        'mapeamento',
        'user_id',
    ];

    protected $casts = [
        'mapeamento' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
