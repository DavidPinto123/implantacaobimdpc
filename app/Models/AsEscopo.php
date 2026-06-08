<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AsEscopo extends Model
{
    protected $table = 'as_escopos';

    protected $fillable = [
        'grupo',
        'grupo_oi_id',
        'numero_as',
        'escopo',
        'item_recebimento',
        'percentual_faturamento_mao_obra_default',
        'percentual_faturamento_material_default',
        'is_active',
        'is_personalizado',
        'created_by',
        'controle_nota_fiscal_id',
        'capex_simulacao_item_id',
    ];

    protected $casts = [
        'percentual_faturamento_mao_obra_default' => 'decimal:2',
        'percentual_faturamento_material_default' => 'decimal:2',
        'is_active' => 'boolean',
        'is_personalizado' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (AsEscopo $escopo): void {
            $maoObra = round(max(0, min(100, (float) ($escopo->percentual_faturamento_mao_obra_default ?? 60))), 2);

            $escopo->percentual_faturamento_mao_obra_default = $maoObra;
            $escopo->percentual_faturamento_material_default = round(100 - $maoObra, 2);
        });
    }

    public function faixasArea()
    {
        return $this->belongsToMany(
            AsFaixaArea::class,
            'as_escopo_faixa_area',
            'as_escopo_id',
            'as_faixa_area_id'
        )->withPivot('valor_m2')->withTimestamps();
    }

    public function marcas(): BelongsToMany
    {
        return $this->belongsToMany(Marca::class, 'as_escopo_marca')
            ->withTimestamps();
    }

    public function criadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function controleNotaFiscal(): BelongsTo
    {
        return $this->belongsTo(ControleNotaFiscal::class);
    }

    public function capexSimulacaoItem(): BelongsTo
    {
        return $this->belongsTo(CapexSimulacaoItem::class);
    }

    public function grupoOi(): BelongsTo
    {
        return $this->belongsTo(GrupoOi::class, 'grupo_oi_id');
    }

    public function scopeGlobais(Builder $query): Builder
    {
        return $query->whereNull('controle_nota_fiscal_id');
    }

    public function controleNotaFiscalItens(): HasMany
    {
        return $this->hasMany(ControleNotaFiscalItem::class, 'as_escopo_id');
    }
}
