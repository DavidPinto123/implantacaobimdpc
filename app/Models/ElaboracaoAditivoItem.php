<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ElaboracaoAditivoItem extends Model
{
    protected $table = 'elaboracao_aditivo_items';

    protected $fillable = [
        'elaboracao_aditivo_id',
        'item',
        'descricao_servico',
        'quantidade',
        'unidade',
        'valor_material_unitario',
        'valor_mao_obra_unitario',
        'total_unitario',
        'valor_total_geral',
    ];

    protected $casts = [
        'quantidade' => 'decimal:2',
        'valor_material_unitario' => 'decimal:2',
        'valor_mao_obra_unitario' => 'decimal:2',
        'total_unitario' => 'decimal:2',
        'valor_total_geral' => 'decimal:2',
    ];

    public function elaboracaoAditivo(): BelongsTo
    {
        return $this->belongsTo(ElaboracaoAditivo::class);
    }
}
