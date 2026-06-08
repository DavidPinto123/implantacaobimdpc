<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class ControleNotaFiscalAuxiliar extends Model
{
    protected $table = 'controle_nota_fiscal_auxiliares';

    public const GRUPOS_AUXILIARES_FIXOS = [
        'Projeto',
        'Solicitação Cliente',
        'Legalização',
        'Shell',
        'Orçamentos',
    ];

    public const GRUPOS_AUXILIARES_ALIASES = [
        'Projeto' => 'Projeto',
        'Projetos' => 'Projeto',
        'Solicitação' => 'Solicitação Cliente',
        'Solicitacao' => 'Solicitação Cliente',
        'Cliente' => 'Solicitação Cliente',
        'Solicitação Cliente' => 'Solicitação Cliente',
        'Solicitacao Cliente' => 'Solicitação Cliente',
        'Legalização' => 'Legalização',
        'Legalizacao' => 'Legalização',
        'Shell' => 'Shell',
        'Orçamento' => 'Orçamentos',
        'Orcamento' => 'Orçamentos',
        'Orçamentos' => 'Orçamentos',
        'Orcamentos' => 'Orçamentos',
    ];

    protected $fillable = [
        'controle_nota_fiscal_id',
        'grupo',
        'numero_as',
        'numero_complemento',
        'escopo',
        'empresa',
        'percentual_total',
        'percentual_faturamento_mao_obra',
        'percentual_faturamento_material',
        'valor_global_a',
        'total_medicao_a_menos_b',
        'valor_acumulado_medido',
        'saldo',
        'observacoes',
        'liberado_para_fornecedor_at',
        'sort_order',
    ];

    protected $casts = [
        'percentual_total' => 'decimal:2',
        'percentual_faturamento_mao_obra' => 'decimal:2',
        'percentual_faturamento_material' => 'decimal:2',
        'valor_global_a' => 'decimal:2',
        'total_medicao_a_menos_b' => 'decimal:2',
        'valor_acumulado_medido' => 'decimal:2',
        'saldo' => 'decimal:2',
        'liberado_para_fornecedor_at' => 'datetime',
    ];

    public function controleNotaFiscal(): BelongsTo
    {
        return $this->belongsTo(ControleNotaFiscal::class);
    }

    public function asas(): HasMany
    {
        return $this->hasMany(Asa::class, 'controle_nota_fiscal_auxiliar_id');
    }

    public function notasFiscais(): HasManyThrough
    {
        return $this->hasManyThrough(
            ControleNotaFiscalNota::class,
            Asa::class,
            'controle_nota_fiscal_auxiliar_id',
            'autorizacao_servico_adicional_id',
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

    public static function normalizeGrupo(?string $grupo): ?string
    {
        if ($grupo === null) {
            return null;
        }

        $grupoNormalizado = trim($grupo);

        if ($grupoNormalizado === '') {
            return null;
        }

        return self::GRUPOS_AUXILIARES_ALIASES[$grupoNormalizado] ?? $grupoNormalizado;
    }
}
