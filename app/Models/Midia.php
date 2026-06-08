<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Midia extends Model
{
    protected $table = 'midias';

    protected $fillable = [
        'mediavel_type',
        'mediavel_id',
        'path',
        'disk',
        'categoria',
        'tipo',
        'nome_original',
        'ordem',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'ordem' => 'integer',
    ];

    public function mediavel(): MorphTo
    {
        return $this->morphTo();
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    public function scopeImagens($query)
    {
        return $query->where('tipo', 'imagem');
    }

    public function scopeCategoria($query, string $categoria)
    {
        return $query->where('categoria', $categoria);
    }
}
