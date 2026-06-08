<?php

namespace App\Models\PosObra;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MensagemWhatsapp extends Model
{
    protected $table = 'po_mensagens_whatsapp';

    protected $fillable = [
        'pendencia_id',
        'telefone',
        'direcao',
        'mensagem',
        'tipo',
        'midia_url',
        'status_entrega',
        'wamid',
    ];

    public function pendencia(): BelongsTo
    {
        return $this->belongsTo(Pendencia::class, 'pendencia_id');
    }
}
