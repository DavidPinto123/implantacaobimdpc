<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ControleNotaFiscalItem extends Model
{
    protected ?int $legacyAutorizacaoServicoId = null;

    protected $fillable = [
        'controle_nota_fiscal_id',
        'capex_simulacao_item_id',
        'as_escopo_id',
        'grupo',
        'numero_as',
        'numero_complemento',
        'escopo_complementar',
        'escopo',
        'empresa',
        'valor_estimado_as',
        'valor_estimado_as_simulador',
        'valor_estimado_as_editado_manualmente',
        'quantidade',
        'percentual_total',
        'percentual_faturamento_mao_obra',
        'percentual_faturamento_material',
        'valor_global_a',
        'total_medicao_a_menos_b',
        'valor_acumulado_medido',
        'saldo',
        'observacoes',
        'liberado_para_fornecedor_at',
        'data_entrega',
        'sort_order',
        'status_retrofit',
    ];

    protected static function booted(): void
    {
        static::saved(function (ControleNotaFiscalItem $item): void {
            if ($item->legacyAutorizacaoServicoId === null) {
                return;
            }

            AutorizacaoServico::query()
                ->whereKey($item->legacyAutorizacaoServicoId)
                ->update(['controle_nota_fiscal_item_id' => $item->id]);

            $item->legacyAutorizacaoServicoId = null;
        });
    }

    public function setAutorizacaoServicoIdAttribute(mixed $value): void
    {
        $this->legacyAutorizacaoServicoId = filled($value) ? (int) $value : null;
    }

    protected $casts = [
        'quantidade' => 'decimal:2',
        'percentual_total' => 'decimal:2',
        'percentual_faturamento_mao_obra' => 'decimal:2',
        'percentual_faturamento_material' => 'decimal:2',
        'valor_estimado_as' => 'decimal:2',
        'valor_estimado_as_simulador' => 'decimal:2',
        'valor_estimado_as_editado_manualmente' => 'boolean',
        'valor_global_a' => 'decimal:2',
        'total_medicao_a_menos_b' => 'decimal:2',
        'valor_acumulado_medido' => 'decimal:2',
        'saldo' => 'decimal:2',
        'liberado_para_fornecedor_at' => 'datetime',
        'data_entrega' => 'date',
    ];

    public function controleNotaFiscal(): BelongsTo
    {
        return $this->belongsTo(ControleNotaFiscal::class);
    }

    public function asEscopo(): BelongsTo
    {
        return $this->belongsTo(AsEscopo::class, 'as_escopo_id');
    }

    public function autorizacaoServico(): HasOne
    {
        return $this->hasOne(AutorizacaoServico::class, 'controle_nota_fiscal_item_id');
    }

    public function notasFiscais(): HasManyThrough
    {
        return $this->hasManyThrough(
            ControleNotaFiscalNota::class,
            AutorizacaoServico::class,
            'controle_nota_fiscal_item_id',
            'autorizacao_servico_id',
            'id',
            'id',
        )
            ->orderBy('controle_nota_fiscal_notas.sort_order')
            ->orderBy('controle_nota_fiscal_notas.id');
    }

    public function notas(): HasManyThrough
    {
        return $this->notasFiscais();
    }

    public function notasMaoObra(): HasManyThrough
    {
        return $this->notasFiscais()->where('tipo_medicao', 'mao_obra');
    }

    public function notasMaterial(): HasManyThrough
    {
        return $this->notasFiscais()->where('tipo_medicao', 'material');
    }

    public function notasIndiretas(): HasManyThrough
    {
        return $this->notasFiscais()->whereIn('tipo_medicao', ControleNotaFiscalNota::tiposMaterialBucket());
    }
}
