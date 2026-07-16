<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AmbientacaoImagemComentario extends Model
{
    protected $table = 'ambientacao_imagem_comentarios';

    protected $fillable = [
        'ambientacao_imagem_id',
        'user_id',
        'comentario',
    ];

    public function imagem(): BelongsTo
    {
        return $this->belongsTo(AmbientacaoImagem::class, 'ambientacao_imagem_id');
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
