<?php

namespace App\Models;

use App\Enums\FaseCronograma;
use App\Enums\GatilhoTemplateFase;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CronogramaFaseDependencia extends Model
{
    use HasFactory;

    protected $table = 'cronograma_fase_dependencias';

    protected $fillable = [
        'cronograma_fase_id',
        'depende_de_fase',
        'depende_de_item_id',
        'gatilho',
        'gap_dias',
        'regra_customizada',
    ];

    protected $casts = [
        'depende_de_fase' => FaseCronograma::class,
        'depende_de_item_id' => 'integer',
        'gatilho' => GatilhoTemplateFase::class,
        'gap_dias' => 'integer',
        'regra_customizada' => 'boolean',
    ];

    public function fase(): BelongsTo
    {
        return $this->belongsTo(CronogramaFase::class, 'cronograma_fase_id');
    }

    public function dependeDeItem(): BelongsTo
    {
        return $this->belongsTo(CronogramaFaseItem::class, 'depende_de_item_id');
    }
}
