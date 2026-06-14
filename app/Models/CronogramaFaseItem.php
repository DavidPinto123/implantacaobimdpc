<?php

namespace App\Models;

use App\Enums\StatusLiberacaoPosse;
use App\Models\User;
use App\Observers\CronogramaFaseItemObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([CronogramaFaseItemObserver::class])]
class CronogramaFaseItem extends Model
{
    protected $table = 'cronograma_fase_itens';

    protected $fillable = [
        'cronograma_fase_id',
        'parent_id',
        'depende_de_item_id',
        'depende_de_fase_id',
        'titulo',
        'valor',
        'revisor_id',
        'descricao',
        'recebido',
        'status_liberacao',
        'observacoes',
        'ordem',
        'duracao_dias',
        'origem',
        'data_prevista_inicio',
        'data_prevista_fim',
        'data_prevista_manual',
        'data_realizada_inicio',
        'data_realizada_fim',
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'depende_de_item_id' => 'integer',
        'depende_de_fase_id' => 'integer',
        'revisor_id' => 'integer',
        'recebido' => 'boolean',
        'status_liberacao' => StatusLiberacaoPosse::class,
        'ordem' => 'integer',
        'duracao_dias' => 'integer',
        'valor' => 'decimal:2',
        'data_prevista_inicio' => 'date',
        'data_prevista_fim' => 'date',
        'data_prevista_manual' => 'boolean',
        'data_realizada_inicio' => 'date',
        'data_realizada_fim' => 'date',
    ];

    public function fase(): BelongsTo
    {
        return $this->belongsTo(CronogramaFase::class, 'cronograma_fase_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(CronogramaFaseItem::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(CronogramaFaseItem::class, 'parent_id')->orderBy('ordem');
    }

    public function dependeDeItem(): BelongsTo
    {
        return $this->belongsTo(CronogramaFaseItem::class, 'depende_de_item_id');
    }

    public function dependeDeFase(): BelongsTo
    {
        return $this->belongsTo(CronogramaFase::class, 'depende_de_fase_id');
    }

    public function dependencias(): HasMany
    {
        return $this->hasMany(CronogramaFaseItemDependencia::class, 'cronograma_fase_item_id');
    }

    public function dependentes(): HasMany
    {
        return $this->hasMany(CronogramaFaseItem::class, 'depende_de_item_id')->orderBy('ordem');
    }

    public function responsaveis(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'cronograma_fase_item_responsaveis');
    }

    public function revisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revisor_id');
    }

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }
}
