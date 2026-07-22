<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Orcamento extends Model
{
    protected $table = 'orcamentos';

    protected $fillable = [
        'projeto_id',
        'nome',
        'nome_mkt',
        'arquivo_revit',
        'revisao',
        'revit_sincronizado_em',
        'data',
        'criado_por',
    ];

    protected $casts = [
        'data'                   => 'date',
        'revit_sincronizado_em'  => 'datetime',
    ];

    public function getRevisaoFormatadaAttribute(): string
    {
        return 'R' . str_pad((string) $this->revisao, 3, '0', STR_PAD_LEFT);
    }

    public function projeto(): BelongsTo
    {
        return $this->belongsTo(Projeto::class);
    }

    public function criador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'criado_por');
    }

    public function categorias(): HasMany
    {
        return $this->hasMany(OrcamentoCategoria::class)->orderBy('ordem');
    }

    public function getTotalMaterialAttribute(): float
    {
        return $this->categorias->sum(fn (OrcamentoCategoria $c) => $c->total_material);
    }

    public function getTotalMaoDeObraAttribute(): float
    {
        return $this->categorias->sum(fn (OrcamentoCategoria $c) => $c->total_mao_de_obra);
    }

    public function getTotalGeralAttribute(): float
    {
        return $this->categorias->sum(fn (OrcamentoCategoria $c) => $c->total_geral);
    }
}
