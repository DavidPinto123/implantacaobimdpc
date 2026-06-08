<?php

namespace App\Models;

use App\Enums\ModoAncoraCronograma;
use App\Enums\TipoObraCronograma;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CronogramaTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cronograma_templates';

    protected $fillable = [
        'nome',
        'tipo_obra',
        'ancora_campo',
        'modo_ancora',
        'template_pareado_id',
        'ativo',
        'observacoes',
    ];

    protected $casts = [
        'tipo_obra' => TipoObraCronograma::class,
        'modo_ancora' => ModoAncoraCronograma::class,
        'ativo' => 'boolean',
    ];

    public function fases(): HasMany
    {
        return $this->hasMany(CronogramaTemplateFase::class)->orderBy('ordem');
    }

    public function pareado(): BelongsTo
    {
        return $this->belongsTo(self::class, 'template_pareado_id');
    }

    /**
     * Retorna o template do par correspondente ao modo de âncora desejado.
     * Se o próprio template já é desse modo, devolve a si mesmo. Se o par
     * existe e bate com o modo, devolve o par. Caso contrário, null.
     */
    public function variantePara(ModoAncoraCronograma|string $modo): ?self
    {
        $alvo = $modo instanceof ModoAncoraCronograma ? $modo : ModoAncoraCronograma::from($modo);

        if ($this->modo_ancora === $alvo) {
            return $this;
        }

        $par = $this->pareado;

        return $par && $par->modo_ancora === $alvo ? $par : null;
    }

    public function temPar(): bool
    {
        return $this->template_pareado_id !== null;
    }

    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }

    public function scopeParaTipoObra($query, TipoObraCronograma|string $tipo)
    {
        $valor = $tipo instanceof TipoObraCronograma ? $tipo->value : $tipo;

        return $query->where('tipo_obra', $valor);
    }
}
