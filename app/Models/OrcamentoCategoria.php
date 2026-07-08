<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrcamentoCategoria extends Model
{
    protected $table = 'orcamento_categorias';

    protected $fillable = [
        'orcamento_id',
        'nome',
        'ordem',
    ];

    public function orcamento(): BelongsTo
    {
        return $this->belongsTo(Orcamento::class);
    }

    public function itens(): HasMany
    {
        return $this->hasMany(OrcamentoItem::class, 'orcamento_categoria_id')->orderBy('ordem');
    }

    public function getTotalMaterialAttribute(): float
    {
        return $this->itens->sum(fn (OrcamentoItem $item) => (float) $item->quantidade * (float) $item->valor_mat);
    }

    public function getTotalMaoDeObraAttribute(): float
    {
        return $this->itens->sum(fn (OrcamentoItem $item) => (float) $item->quantidade * (float) $item->valor_mo);
    }

    public function getTotalGeralAttribute(): float
    {
        return $this->itens->sum(fn (OrcamentoItem $item) => $item->valor_total);
    }
}
