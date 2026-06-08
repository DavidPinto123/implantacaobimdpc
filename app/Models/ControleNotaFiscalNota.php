<?php

namespace App\Models;

use App\Enums\StatusControleNotaFiscalNota;
use App\Support\Cnpj;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

class ControleNotaFiscalNota extends Model
{
    public const TIPO_MEDICAO_MAO_OBRA = 'mao_obra';

    public const TIPO_MEDICAO_MATERIAL = 'material';

    public const TIPO_MEDICAO_TRANSPORTE = 'transporte';

    protected $fillable = [
        'autorizacao_servico_id',
        'autorizacao_servico_adicional_id',
        'importado_por_id',
        'decidido_por_id',
        'tipo_medicao',
        'empresa',
        'cnpj_fornecedor',
        'numero_nf',
        'cnpj_faturamento',
        'instrucoes_pagamento',
        'boleto_path',
        'data_vencimento_boleto',
        'banco',
        'banco_codigo',
        'agencia',
        'conta_corrente',
        'valor_acumulado_medido_nf',
        'emissao',
        'envio',
        'status',
        'baixado',
        'baixado_por_id',
        'baixado_em',
        'decidido_em',
        'arquivo_path',
        'observacoes',
        'sort_order',
    ];

    protected $casts = [
        'valor_acumulado_medido_nf' => 'decimal:2',
        'baixado' => 'boolean',
        'emissao' => 'date',
        'data_vencimento_boleto' => 'date',
        'recebimento' => 'date',
        'envio' => 'date',
        'decidido_em' => 'datetime',
        'baixado_em' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $nota): void {
            $nota->numero_nf_cnpj_fornecedor_hash = static::buildDuplicateHash(
                $nota->numero_nf,
                $nota->cnpj_fornecedor,
            );
        });
    }

    public static function normalizeNumeroNotaFiscal(?string $value): string
    {
        $digits = preg_replace('/\D/', '', (string) $value) ?? '';

        return ltrim($digits, '0');
    }

    public static function normalizeCnpjFornecedor(?string $value): string
    {
        return Cnpj::normalize($value);
    }

    public static function buildDuplicateHash(?string $numeroNotaFiscal, ?string $cnpjFornecedor): ?string
    {
        $normalizedNumeroNotaFiscal = static::normalizeNumeroNotaFiscal($numeroNotaFiscal);
        $normalizedCnpjFornecedor = static::normalizeCnpjFornecedor($cnpjFornecedor);

        if ($normalizedNumeroNotaFiscal === '' || $normalizedCnpjFornecedor === '') {
            return null;
        }

        return hash('sha256', $normalizedNumeroNotaFiscal.'|'.$normalizedCnpjFornecedor);
    }

    public static function duplicateExists(?string $numeroNotaFiscal, ?string $cnpjFornecedor, ?int $exceptId = null): bool
    {
        $duplicateHash = static::buildDuplicateHash($numeroNotaFiscal, $cnpjFornecedor);

        if ($duplicateHash === null) {
            return false;
        }

        return static::query()
            ->when($exceptId !== null, fn (Builder $query): Builder => $query->whereKeyNot($exceptId))
            ->where('numero_nf_cnpj_fornecedor_hash', $duplicateHash)
            ->exists();
    }

    public function autorizacaoServico(): BelongsTo
    {
        return $this->belongsTo(AutorizacaoServico::class, 'autorizacao_servico_id');
    }

    public function asa(): BelongsTo
    {
        return $this->belongsTo(Asa::class, 'autorizacao_servico_adicional_id');
    }

    public function itemDerivado(): ?ControleNotaFiscalItem
    {
        return $this->autorizacaoServico?->controleNotaFiscalItem;
    }

    public function auxiliarDerivado(): ?ControleNotaFiscalAuxiliar
    {
        return $this->asa?->controleNotaFiscalAuxiliar;
    }

    public function documentoFaturavel(): Model
    {
        return $this->autorizacaoServico ?? $this->asa;
    }

    public function importadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'importado_por_id');
    }

    public function decididoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decidido_por_id');
    }

    public function baixadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'baixado_por_id');
    }

    public function baixas(): HasMany
    {
        return $this->hasMany(ControleNotaFiscalNotaBaixa::class)->latest('baixado_em');
    }

    public function ultimaBaixa(): HasOne
    {
        return $this->hasOne(ControleNotaFiscalNotaBaixa::class)->latestOfMany('baixado_em');
    }

    /**
     * Retorna a Obra vinculada à nota, resolvendo via item ou auxiliar.
     */
    public function getObraAttribute(): ?Obras
    {
        return $this->autorizacaoServico?->controleNotaFiscalItem?->controleNotaFiscal?->obra
            ?? $this->asa?->controleNotaFiscalAuxiliar?->controleNotaFiscal?->obra;
    }

    public function isPrincipal(): bool
    {
        return filled($this->autorizacao_servico_id);
    }

    public function isAdicional(): bool
    {
        return filled($this->autorizacao_servico_adicional_id);
    }

    /**
     * @return array<int, string>
     */
    public static function tiposMaterialBucket(): array
    {
        return [
            self::TIPO_MEDICAO_MATERIAL,
            self::TIPO_MEDICAO_TRANSPORTE,
        ];
    }

    public function scopeTipoMaterialBucket(Builder $query): Builder
    {
        return $query->whereIn($this->getTable().'.tipo_medicao', static::tiposMaterialBucket());
    }

    public static function getUploadDisk(): string
    {
        return (string) config('filesystems.media_disk', 'r2');
    }

    public static function getStatusOptions(): array
    {
        return StatusControleNotaFiscalNota::options();
    }

    public static function getStatusLabel(?string $status): string
    {
        return StatusControleNotaFiscalNota::labelFrom($status);
    }

    public static function getStatusColor(?string $status): string
    {
        return StatusControleNotaFiscalNota::colorFrom($status);
    }

    public static function getFileUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk(static::getUploadDisk());

        return $disk->url($path);
    }
}
