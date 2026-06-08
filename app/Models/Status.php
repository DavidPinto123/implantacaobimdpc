<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    protected $table = 'statuses';

    protected $fillable = [
        'contexto',
        'slug',
        'nome',
        'cor',
        'ordem',
        'is_active',
        'is_protected',
        'tipo_custo',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_protected' => 'boolean',
    ];

    public function scopeNoContexto(Builder $query, string $contexto): Builder
    {
        return $query->where('contexto', $contexto);
    }

    /**
     * @return Collection<int, self>
     */
    public static function ativosPorContexto(string $contexto): Collection
    {
        return static::query()
            ->where('contexto', $contexto)
            ->where('is_active', true)
            ->orderBy('ordem')
            ->get();
    }

    /**
     * Slugs ativos do contexto (útil para validação `array_key_exists`).
     *
     * @return array<string, string> slug => nome
     */
    public static function slugsDoContexto(string $contexto): array
    {
        return static::ativosPorContexto($contexto)
            ->mapWithKeys(fn (self $s): array => [$s->slug => $s->nome])
            ->all();
    }

    public static function porSlug(string $contexto, string $slug): ?self
    {
        return static::query()
            ->where('contexto', $contexto)
            ->where('slug', $slug)
            ->first();
    }
}
