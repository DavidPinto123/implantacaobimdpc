<?php

namespace App\Models\PosObra;

use App\Enums\PosObra\TipoAnexo;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnexoPendencia extends Model
{
    protected $table = 'po_anexos_pendencias';

    protected $fillable = [
        'pendencia_id',
        'tipo',
        'url',
        'nome_arquivo',
        'uploaded_by',
    ];

    protected $casts = [
        'tipo' => TipoAnexo::class,
    ];

    public function pendencia(): BelongsTo
    {
        return $this->belongsTo(Pendencia::class, 'pendencia_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
