<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GrupoOi extends Model
{
    protected $table = 'grupo_ois';

    protected $fillable = [
        'parent_id',
        'nome',
        'nivel',
        'ordem',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'nivel' => 'integer',
            'ordem' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('ordem');
    }

    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }

    public function asEscopos(): HasMany
    {
        return $this->hasMany(AsEscopo::class, 'grupo_oi_id');
    }

    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    public function isLeaf(): bool
    {
        return $this->children()->doesntExist();
    }

    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeRaizes(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }
}
