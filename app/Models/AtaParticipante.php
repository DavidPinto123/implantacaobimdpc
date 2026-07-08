<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AtaParticipante extends Model
{
    protected $table = 'ata_participantes';

    protected $fillable = ['ata_id', 'user_id', 'nome', 'empresa', 'cargo', 'email'];

    public function ata(): BelongsTo
    {
        return $this->belongsTo(Ata::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
