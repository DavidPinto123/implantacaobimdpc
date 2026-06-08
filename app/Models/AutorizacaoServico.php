<?php

namespace App\Models;

use App\Enums\AsStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AutorizacaoServico extends Model
{
    protected $table = 'autorizacao_servicos';

    protected $fillable = [
        'obra_id',
        'controle_nota_fiscal_item_id',
        'as_escopo_id',
        'construtora_id',
        'status',
        'numero_as',
        'numero_complemento',
        'valor',
        'desconto_autorizacao_servico',
        'valor_estimado',
        'valor_inicial',
        'parcelamento_autorizacao_servico',
        'data_inicio_servico',
        'data_termino_servico',
        'data_entrega_material',
        'tipo_contratacao',
        'descricao_servico_pdf',
        'itens_descricao_servico_pdf',
        'anexos_autorizacao_servico',
        'anexo_autorizacao_servico',
        'observacoes',
        'created_by_id',
        'enviado_em',
        'enviado_por_id',
        'cancelado_em',
        'cancelado_por_id',
        'motivo_cancelamento',
    ];

    protected $casts = [
        'status' => AsStatus::class,
        'valor' => 'decimal:2',
        'desconto_autorizacao_servico' => 'decimal:2',
        'valor_estimado' => 'decimal:2',
        'valor_inicial' => 'decimal:2',
        'parcelamento_autorizacao_servico' => 'array',
        'itens_descricao_servico_pdf' => 'array',
        'anexos_autorizacao_servico' => 'array',
        'data_inicio_servico' => 'date',
        'data_termino_servico' => 'date',
        'data_entrega_material' => 'date',
        'enviado_em' => 'datetime',
        'cancelado_em' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (AutorizacaoServico $as): void {
            $numero = (string) ($as->numero_as ?? '');
            $as->numero_complemento = filled($as->numero_complemento)
                ? (string) $as->numero_complemento
                : '';
            $as->numero_as_hash = $numero !== '' ? hash('sha256', $numero) : null;
        });
    }

    public function obra(): BelongsTo
    {
        return $this->belongsTo(Obras::class, 'obra_id');
    }

    public function asEscopo(): BelongsTo
    {
        return $this->belongsTo(AsEscopo::class, 'as_escopo_id');
    }

    public function construtora(): BelongsTo
    {
        return $this->belongsTo(Construtora::class, 'construtora_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function enviadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enviado_por_id');
    }

    public function canceladoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelado_por_id');
    }

    public function controleNotaFiscalItem(): BelongsTo
    {
        return $this->belongsTo(ControleNotaFiscalItem::class, 'controle_nota_fiscal_item_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(ControleNotaFiscalItem::class, 'id', 'controle_nota_fiscal_item_id');
    }

    public function notasFiscais(): HasMany
    {
        return $this->hasMany(ControleNotaFiscalNota::class, 'autorizacao_servico_id');
    }
}
