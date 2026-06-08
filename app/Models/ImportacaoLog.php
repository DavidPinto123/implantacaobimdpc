<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportacaoLog extends Model
{
    protected $table = 'importacao_logs';

    protected $fillable = [
        'arquivo_original',
        'arquivo_path',
        'modulo',
        'status',
        'total_linhas',
        'linhas_criadas',
        'linhas_atualizadas',
        'linhas_erro',
        'erros',
        'mapeamento_usado',
        'user_id',
        'iniciado_em',
        'finalizado_em',
    ];

    protected $casts = [
        'erros' => 'array',
        'mapeamento_usado' => 'array',
        'iniciado_em' => 'datetime',
        'finalizado_em' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function stagingRows(): HasMany
    {
        return $this->hasMany(ImportacaoStaging::class);
    }
}
