<?php

namespace App\Models\PosObra;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AprovacaoFinalizacao extends Model
{
    protected $table = 'po_aprovacoes_finalizacao';

    protected $fillable = [
        'pendencia_id',
        'solicitado_por',
        'aprovado_por',
        'status',
        'motivo_rejeicao',
    ];

    // status: PENDENTE | APROVADA | REJEITADA
    public function pendencia(): BelongsTo
    {
        return $this->belongsTo(Pendencia::class, 'pendencia_id');
    }

    public function solicitadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitado_por');
    }

    public function aprovadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprovado_por');
    }
}
