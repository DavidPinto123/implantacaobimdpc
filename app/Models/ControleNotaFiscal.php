<?php

namespace App\Models;

use App\Enums\TipoUnidade;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ControleNotaFiscal extends Model
{
    public const STATUS_ATIVO = 'ativo';

    public const STATUS_RASCUNHO = 'rascunho';

    public const STATUS_AGUARDANDO_CONSTRUTORA = 'aguardando_construtora';

    public const STATUS_AGUARDANDO_FINANCEIRO = 'aguardando_financeiro';

    public const STATUS_APROVADO = 'aprovado';

    public const STATUS_REPROVADO = 'reprovado';

    public const STATUS_ENCERRADO = 'encerrado';

    public const PERMISSION_CLOSE = 'Close:ControleNotaFiscal';

    protected $fillable = [
        'autorizacao_servico_adicional_id',
        'elaboracao_aditivo_id',
        'obra_id',
        'tipo_unidade',
        'status',
        'data_base',
        'unidade',
        'sigla',
        'endereco',
        'construtora_notificada_em',
        'financeiro_aprovado_por_id',
        'financeiro_aprovado_em',
    ];

    protected $casts = [
        'data_base' => 'date',
        'construtora_notificada_em' => 'datetime',
        'financeiro_aprovado_em' => 'datetime',
    ];

    public function asa(): BelongsTo
    {
        return $this->belongsTo(Asa::class, 'autorizacao_servico_adicional_id');
    }

    public function elaboracaoAditivo(): BelongsTo
    {
        return $this->belongsTo(ElaboracaoAditivo::class, 'elaboracao_aditivo_id');
    }

    public function obra(): BelongsTo
    {
        return $this->belongsTo(Obras::class, 'obra_id')->withoutGlobalScopes();
    }

    public function obraEstaSoftDelete(): bool
    {
        if (! $this->obra_id) {
            return false;
        }

        if ($this->relationLoaded('obra') && $this->obra instanceof Obras) {
            return $this->obra->trashed();
        }

        return Obras::withTrashed()
            ->whereKey($this->obra_id)
            ->whereNotNull('deleted_at')
            ->exists();
    }

    public function getTipoUnidadeLabelAttribute(): string
    {
        return $this->tipo_unidade === TipoUnidade::RETROFIT->value
            ? TipoUnidade::RETROFIT->label()
            : TipoUnidade::EXPANSAO->label();
    }

    public function isRetrofit(): bool
    {
        return $this->tipo_unidade === TipoUnidade::RETROFIT->value;
    }

    public static function resolveTipoUnidade(?Obras $obra = null, bool $forcarRetrofit = false): string
    {
        if ($forcarRetrofit) {
            return TipoUnidade::RETROFIT->value;
        }

        $tiposUnidade = collect($obra?->tipos_unidade ?? [])
            ->map(fn ($tipo) => trim((string) $tipo))
            ->filter(fn (string $tipo) => $tipo !== '')
            ->all();

        if (in_array(TipoUnidade::RETROFIT->value, $tiposUnidade, true)) {
            return TipoUnidade::RETROFIT->value;
        }

        return TipoUnidade::EXPANSAO->value;
    }

    public function financeiroAprovador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'financeiro_aprovado_por_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(ControleNotaFiscalItem::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function auxiliares(): HasMany
    {
        return $this->hasMany(ControleNotaFiscalAuxiliar::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function notasMaoObra(): Builder
    {
        return $this->notasFiscaisPorTipo(['mao_obra']);
    }

    public function notasMaterial(): Builder
    {
        return $this->notasFiscaisPorTipo(['material']);
    }

    public function notasIndiretas(): Builder
    {
        return $this->notasFiscaisPorTipo(ControleNotaFiscalNota::tiposMaterialBucket());
    }

    /**
     * @param  array<int, string>  $tipos
     */
    protected function notasFiscaisPorTipo(array $tipos): Builder
    {
        return ControleNotaFiscalNota::query()
            ->whereIn('tipo_medicao', $tipos)
            ->where(function ($query): void {
                $query
                    ->whereHas('autorizacaoServico.controleNotaFiscalItem', fn ($itemQuery) => $itemQuery
                        ->where('controle_nota_fiscal_id', $this->getKey()))
                    ->orWhereHas('asa.controleNotaFiscalAuxiliar', fn ($auxiliarQuery) => $auxiliarQuery
                        ->where('controle_nota_fiscal_id', $this->getKey()));
            });
    }
}
