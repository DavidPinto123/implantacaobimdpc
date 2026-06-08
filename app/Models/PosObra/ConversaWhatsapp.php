<?php

namespace App\Models\PosObra;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversaWhatsapp extends Model
{
    protected $table = 'po_conversas_whatsapp';

    protected $fillable = [
        'telefone',
        'pendencia_id',
        'perfil',
        'fase',
        'contexto',
        'ultima_mensagem_at',
    ];

    protected $casts = [
        'contexto' => 'array',
        'ultima_mensagem_at' => 'datetime',
    ];

    // perfil: LIDER | CONSTRUTORA | GESTOR

    public function pendencia(): BelongsTo
    {
        return $this->belongsTo(Pendencia::class, 'pendencia_id');
    }
}
