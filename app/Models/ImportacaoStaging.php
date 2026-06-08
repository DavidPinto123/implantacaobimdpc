<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportacaoStaging extends Model
{
    protected $table = 'importacao_staging';

    protected $fillable = [
        'importacao_log_id',
        'linha_planilha',
        'codigo',
        'acao',
        'obra_existente_id',
        'dados',
        'conflitos',
        'erro',
    ];

    protected $casts = [
        'dados' => 'array',
        'conflitos' => 'array',
        'erro' => 'array',
    ];

    public function importacaoLog(): BelongsTo
    {
        return $this->belongsTo(ImportacaoLog::class);
    }

    public function obraExistente(): BelongsTo
    {
        return $this->belongsTo(Obras::class, 'obra_existente_id');
    }
}
