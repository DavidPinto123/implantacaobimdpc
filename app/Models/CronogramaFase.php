<?php

namespace App\Models;

use App\Enums\FaseCronograma;
use App\Enums\StatusCronograma;
use App\Enums\TipoDiasTemplate;
use App\Observers\CronogramaFaseObserver;
use App\Observers\CronogramaFaseSyncObserver;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([CronogramaFaseObserver::class, CronogramaFaseSyncObserver::class])]
class CronogramaFase extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cronograma_fases';

    protected $fillable = [
        'projeto_id',
        'obras_id',
        'fase',
        'titulo_personalizado',
        'ordem',
        'marco',
        'cronograma_template_id',
        'cronograma_template_fase_id',
        'data_prevista_inicio',
        'data_prevista_fim',
        'data_realizada_inicio',
        'data_realizada_fim',
        'status',
        'percentual_conclusao',
        'valor',
        'descricao',
        'observacoes',
        'data_aprovacao',
        'metadados',
        'regra_duracao_dias',
        'regra_tipo_dias',
        'regra_customizada',
        'regra_elastica',
        'visivel',
        'bloqueada_pos_contrato',
    ];

    protected $casts = [
        'fase' => FaseCronograma::class,
        'status' => StatusCronograma::class,
        'data_prevista_inicio' => 'date',
        'data_prevista_fim' => 'date',
        'data_realizada_inicio' => 'date',
        'data_realizada_fim' => 'date',
        'data_aprovacao' => 'date',
        'metadados' => 'array',
        'marco' => 'boolean',
        'percentual_conclusao' => 'integer',
        'regra_tipo_dias' => TipoDiasTemplate::class,
        'regra_duracao_dias' => 'integer',
        'regra_customizada' => 'boolean',
        'regra_elastica' => 'boolean',
        'visivel' => 'boolean',
        'bloqueada_pos_contrato' => 'boolean',
        'valor' => 'decimal:2',
    ];

    public function projeto(): BelongsTo
    {
        return $this->belongsTo(Projeto::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(CronogramaTemplate::class, 'cronograma_template_id');
    }

    public function templateFase(): BelongsTo
    {
        return $this->belongsTo(CronogramaTemplateFase::class, 'cronograma_template_fase_id');
    }

    public function dependencias(): HasMany
    {
        return $this->hasMany(CronogramaFaseDependencia::class);
    }

    public function historicos(): HasMany
    {
        return $this->hasMany(CronogramaFaseHistorico::class);
    }

    public function comentarios(): MorphMany
    {
        return $this->morphMany(Comentario::class, 'comentavel');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(CronogramaFaseItem::class)->orderBy('ordem');
    }

    /**
     * Resolve a visibilidade efetiva da fase: override local da obra OU default do template_fase OU true.
     */
    public function isVisivel(): bool
    {
        if ($this->visivel !== null) {
            return (bool) $this->visivel;
        }

        return (bool) ($this->templateFase?->visivel ?? true);
    }

    /**
     * Resolve a regra efetiva da fase: override local > template_fase > defaults.
     * Retorna objeto com duracao_dias, tipo_dias e dependencias[] (já resolvidas como override OR herança).
     */
    public function regraEfetiva(): object
    {
        $tplFase = $this->templateFase;

        // Usa a relation eager-loaded quando disponível (evita N+1 em loops).
        $dependenciasLocais = $this->relationLoaded('dependencias')
            ? $this->dependencias
            : $this->dependencias()->get();

        if ($dependenciasLocais->isNotEmpty()) {
            $dependencias = $dependenciasLocais;
        } elseif ($tplFase) {
            $dependencias = $tplFase->relationLoaded('dependencias')
                ? $tplFase->dependencias
                : $tplFase->dependencias()->get();
        } else {
            $dependencias = collect();
        }

        return (object) [
            'duracao_dias' => $this->regra_duracao_dias ?? $tplFase?->duracao_dias ?? 0,
            'tipo_dias' => $this->regra_tipo_dias ?? $tplFase?->tipo_dias ?? TipoDiasTemplate::CORRIDOS,
            'dependencias' => $dependencias,
            'elastica' => $this->regra_elastica ?? $tplFase?->regra_elastica ?? false,
        ];
    }

    public function scopeVisiveis($query)
    {
        return $query->where(function ($q) {
            $q->where('visivel', true)
                ->orWhere(function ($q2) {
                    $q2->whereNull('visivel')
                        ->whereHas('templateFase', fn ($tf) => $tf->where('visivel', true));
                })
                ->orWhere(function ($q2) {
                    $q2->whereNull('visivel')->whereNull('cronograma_template_fase_id');
                });
        });
    }

    public function scopeMarcos($query)
    {
        return $query->where('marco', true);
    }

    public function scopeAtrasadas($query)
    {
        return $query->where('status', StatusCronograma::ATRASADO);
    }

    public function scopePorProjeto($query, int $projetoId)
    {
        return $query->where('projeto_id', $projetoId);
    }

    public function getDataEntregaShellAttribute(): ?Carbon
    {
        return $this->projeto?->data_entrega_shell;
    }

    public function getDiasAtrasoAttribute(): int
    {
        if (! $this->data_prevista_fim) {
            return 0;
        }

        if ($this->data_realizada_fim) {
            return 0;
        }

        $statusFinais = [
            StatusCronograma::CONCLUIDO,
            StatusCronograma::REALIZADO,
            StatusCronograma::ASSINADO,
            StatusCronograma::FINALIZADO,
            StatusCronograma::PRONTO,
        ];

        if (in_array($this->status, $statusFinais, true)) {
            return 0;
        }

        if (now()->gt($this->data_prevista_fim)) {
            // `diffInDays` em versões novas do Carbon retorna valor com sinal
            // (negativo quando o argumento é anterior). Usamos `absolute: true`
            // para garantir sempre um inteiro positivo representando o atraso.
            return (int) $this->data_prevista_fim->diffInDays(now(), absolute: true);
        }

        return 0;
    }

    /**
     * Retorna o rótulo de exibição da fase — prioriza `titulo_personalizado`
     * quando preenchido (fases ad-hoc), senão usa o label do enum.
     */
    public function getLabelExibicaoAttribute(): string
    {
        if (filled($this->titulo_personalizado)) {
            return (string) $this->titulo_personalizado;
        }

        return $this->fase?->label() ?? '—';
    }

    /**
     * Sinalizador visual (farol) baseado em atraso e progresso da fase:
     *  - verde   : sem atraso e com algum progresso
     *  - amarelo : atraso até 20% da duração planejada
     *  - vermelho: atraso > 20% OU atrasado sem nenhum progresso
     *  - neutro  : sem dados suficientes / concluído
     */
    public function getFarolAttribute(): string
    {
        $statusFinais = [
            StatusCronograma::CONCLUIDO,
            StatusCronograma::REALIZADO,
            StatusCronograma::ASSINADO,
            StatusCronograma::FINALIZADO,
            StatusCronograma::PRONTO,
        ];

        if (in_array($this->status, $statusFinais, true)) {
            return 'neutro';
        }

        $dias = $this->dias_atraso;
        if ($dias <= 0) {
            return $this->percentual_conclusao > 0 ? 'verde' : 'neutro';
        }

        $duracao = ($this->data_prevista_inicio && $this->data_prevista_fim)
            ? max(1, (int) $this->data_prevista_inicio->diffInDays($this->data_prevista_fim, absolute: true) + 1)
            : null;

        if ($duracao === null) {
            return 'amarelo';
        }

        $percentualAtraso = ($dias / $duracao) * 100;

        if ($percentualAtraso > 20 || $this->percentual_conclusao === 0) {
            return 'vermelho';
        }

        return 'amarelo';
    }
}
