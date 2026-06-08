<?php

namespace App\Models;

use App\Enums\FaseCronograma;
use App\Enums\TipoDiasTemplate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CronogramaTemplateFase extends Model
{
    use HasFactory;

    protected $table = 'cronograma_template_fases';

    protected $fillable = [
        'cronograma_template_id',
        'fase',
        'titulo_personalizado',
        'ordem',
        'duracao_dias',
        'valor',
        'descricao',
        'tipo_dias',
        'visivel',
        'is_ancora',
        'regra_elastica',
        'observacoes',
    ];

    protected $casts = [
        'fase' => FaseCronograma::class,
        'tipo_dias' => TipoDiasTemplate::class,
        'duracao_dias' => 'integer',
        'ordem' => 'integer',
        'visivel' => 'boolean',
        'is_ancora' => 'boolean',
        'regra_elastica' => 'boolean',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(CronogramaTemplate::class, 'cronograma_template_id');
    }

    public function dependencias(): HasMany
    {
        return $this->hasMany(CronogramaTemplateFaseDependencia::class);
    }

    public function itens(): HasMany
    {
        return $this->hasMany(CronogramaTemplateFaseItem::class)->orderBy('ordem');
    }
}
