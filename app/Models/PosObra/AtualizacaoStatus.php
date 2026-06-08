<?php

namespace App\Models\PosObra;

use App\Enums\PosObra\StatusPendencia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AtualizacaoStatus extends Model
{
    protected $table = 'po_atualizacoes_status';

    protected $fillable = [
        'pendencia_id',
        'status_anterior',
        'status_novo',
        'comentario',
        'atualizado_por',
    ];

    protected $casts = [
        'status_anterior' => StatusPendencia::class,
        'status_novo' => StatusPendencia::class,
    ];

    public function pendencia(): BelongsTo
    {
        return $this->belongsTo(Pendencia::class, 'pendencia_id');
    }
}
