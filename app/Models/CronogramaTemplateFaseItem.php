<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CronogramaTemplateFaseItem extends Model
{
    protected $table = 'cronograma_template_fase_itens';

    protected $fillable = [
        'cronograma_template_fase_id',
        'parent_id',
        'depende_de_item_id',
        'depende_de_template_fase_id',
        'titulo',
        'valor',
        'revisor_id',
        'descricao',
        'observacoes',
        'ordem',
        'duracao_dias',
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'depende_de_item_id' => 'integer',
        'depende_de_template_fase_id' => 'integer',
        'revisor_id' => 'integer',
        'ordem' => 'integer',
        'duracao_dias' => 'integer',
    ];

    public function templateFase(): BelongsTo
    {
        return $this->belongsTo(CronogramaTemplateFase::class, 'cronograma_template_fase_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(CronogramaTemplateFaseItem::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(CronogramaTemplateFaseItem::class, 'parent_id')->orderBy('ordem');
    }

    public function dependeDeItem(): BelongsTo
    {
        return $this->belongsTo(CronogramaTemplateFaseItem::class, 'depende_de_item_id');
    }

    public function dependeDeTemplateFase(): BelongsTo
    {
        return $this->belongsTo(CronogramaTemplateFase::class, 'depende_de_template_fase_id');
    }

    public function dependencias(): HasMany
    {
        return $this->hasMany(CronogramaTemplateFaseItemDependencia::class, 'cronograma_template_fase_item_id');
    }

    public function dependentes(): HasMany
    {
        return $this->hasMany(CronogramaTemplateFaseItem::class, 'depende_de_item_id')->orderBy('ordem');
    }

    public function revisor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'revisor_id');
    }

    public function responsaveis(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\User::class,
            'cronograma_template_fase_item_responsaveis',
            'cronograma_template_fase_item_id',
            'user_id'
        );
    }
}
