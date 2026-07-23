<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrcamentoItem extends Model
{
    protected $table = 'orcamento_itens';

    protected $fillable = [
        'orcamento_categoria_id',
        'codigo',
        'descricao',
        'grupo_catalogo',
        'tipo',
        'unidade',
        'quantidade',
        'valor_mat',
        'valor_mo',
        'ordem',
    ];

    protected $casts = [
        'quantidade' => 'decimal:3',
        'valor_mat'  => 'decimal:2',
        'valor_mo'   => 'decimal:2',
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(OrcamentoCategoria::class, 'orcamento_categoria_id');
    }

    public function getValorMatMoAttribute(): float
    {
        return (float) $this->valor_mat + (float) $this->valor_mo;
    }

    public function getValorTotalAttribute(): float
    {
        return (float) $this->quantidade * $this->valor_mat_mo;
    }
}
