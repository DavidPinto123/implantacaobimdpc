<?php

namespace App\Models;

use App\Enums\FaseCronograma;
use App\Enums\GatilhoTemplateFase;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CronogramaTemplateFaseDependencia extends Model
{
    use HasFactory;

    protected $table = 'cronograma_template_fase_dependencias';

    protected $fillable = [
        'cronograma_template_fase_id',
        'depende_de_fase',
        'depende_de_item_id',
        'gatilho',
        'gap_dias',
    ];

    protected $casts = [
        'depende_de_fase' => FaseCronograma::class,
        'gatilho' => GatilhoTemplateFase::class,
        'gap_dias' => 'integer',
        'depende_de_item_id' => 'integer',
    ];

    public function templateFase(): BelongsTo
    {
        return $this->belongsTo(CronogramaTemplateFase::class, 'cronograma_template_fase_id');
    }

    public function dependeDeItem(): BelongsTo
    {
        return $this->belongsTo(CronogramaTemplateFaseItem::class, 'depende_de_item_id');
    }
}
