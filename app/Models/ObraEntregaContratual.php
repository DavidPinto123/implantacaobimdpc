<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ObraEntregaContratual extends Model
{
    use HasFactory;

    protected $table = 'obra_entregas_contratuais';

    protected $fillable = [
        'obra_id',
        'tipo',
        'entrega',
        'descricao_entrega',
        'descricao_existente',
        'status',
        'data_entrega',
        'custo_estimado',
        'previsto_em_contrato',
        'previsto_status',
        'custo_contrato',
        'custo_sem_contrato',
        'observacoes',
        'sort_order',
    ];

    protected $casts = [
        'data_entrega' => 'date',
        'custo_estimado' => 'decimal:2',
        'previsto_em_contrato' => 'boolean',
        'custo_contrato' => 'decimal:2',
        'custo_sem_contrato' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public function obra(): BelongsTo
    {
        return $this->belongsTo(Obras::class, 'obra_id');
    }

    public function statusModel(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'status', 'slug')
            ->where('contexto', 'entrega_contratual_status');
    }

    public function previstoStatusModel(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'previsto_status', 'slug')
            ->where('contexto', 'entrega_contratual_previsto');
    }
}
