<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsaItem extends Model
{
    protected $table = 'autorizacao_servico_adicional_items';

    protected $fillable = [
        'autorizacao_servico_adicional_id',
        'item',
        'descricao',
        'unidade',
        'quantidade',
        'valor_unitario',
        'valor_total',
    ];

    protected $casts = [
        'quantidade' => 'decimal:2',
        'valor_unitario' => 'decimal:2',
        'valor_total' => 'decimal:2',
    ];

    public function asa(): BelongsTo
    {
        return $this->belongsTo(Asa::class, 'autorizacao_servico_adicional_id');
    }
}
