<?php

namespace App\Models;

use App\Enums\GatilhoTemplateFase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CronogramaTemplateFaseItemDependencia extends Model
{
    protected $table = 'cronograma_template_fase_item_dependencias';

    protected $fillable = [
        'cronograma_template_fase_item_id',
        'depende_de_template_fase_id',
        'depende_de_item_id',
        'gatilho',
        'gap_dias',
    ];

    protected $casts = [
        'depende_de_template_fase_id' => 'integer',
        'depende_de_item_id' => 'integer',
        'gatilho' => GatilhoTemplateFase::class,
        'gap_dias' => 'integer',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(CronogramaTemplateFaseItem::class, 'cronograma_template_fase_item_id');
    }

    public function dependeDeTemplateFase(): BelongsTo
    {
        return $this->belongsTo(CronogramaTemplateFase::class, 'depende_de_template_fase_id');
    }

    public function dependeDeItem(): BelongsTo
    {
        return $this->belongsTo(CronogramaTemplateFaseItem::class, 'depende_de_item_id');
    }
}
