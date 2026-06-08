<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgendaTipoEvento extends Model
{
    protected $table = 'agenda_tipos_evento';

    protected $fillable = [
        'setor_id',
        'slug',
        'nome',
        'cor',
        'created_by',
    ];

    public function setor(): BelongsTo
    {
        return $this->belongsTo(Setor::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
