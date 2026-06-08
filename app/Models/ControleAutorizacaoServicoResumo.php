<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ControleAutorizacaoServicoResumo extends Model
{
    protected $fillable = [
        'obra_id',
        'oi_shell',
        'oi_recheio',
        'valor_inicial_shell',
        'valor_inicial_recheio',
        'valor_final_shell',
        'valor_final_recheio',
        'valor_final_adicional_hp',
        'valor_final_adicional_smart',
    ];

    protected $casts = [
        'oi_shell' => 'decimal:2',
        'oi_recheio' => 'decimal:2',
        'valor_inicial_shell' => 'decimal:2',
        'valor_inicial_recheio' => 'decimal:2',
        'valor_final_shell' => 'decimal:2',
        'valor_final_recheio' => 'decimal:2',
        'valor_final_adicional_hp' => 'decimal:2',
        'valor_final_adicional_smart' => 'decimal:2',
    ];

    public function obra(): BelongsTo
    {
        return $this->belongsTo(Obras::class, 'obra_id');
    }
}
