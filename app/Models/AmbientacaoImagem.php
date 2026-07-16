<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AmbientacaoImagem extends Model
{
    protected $table = 'ambientacao_imagens';

    protected $fillable = [
        'ambientacao_id',
        'arquivo',
        'legenda',
        'origem',
        'yaw',
        'pitch',
        'fov',
        'uploaded_by',
    ];

    public function ambientacao(): BelongsTo
    {
        return $this->belongsTo(Ambientacao::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function comentarios(): HasMany
    {
        return $this->hasMany(AmbientacaoImagemComentario::class);
    }
}
