<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ObraDocumento extends Model
{
    protected $table = 'obra_documentos';

    protected $fillable = [
        'obra_id',
        'construtora_id',
        'nome',
        'status',
        'arquivo_path',
        'arquivo_nome',
        'arquivos_paths',
        'arquivos_nomes',
        'usuario_id',
    ];

    protected function casts(): array
    {
        return [
            'arquivos_paths' => 'array',
            'arquivos_nomes' => 'array',
        ];
    }

    public function obra(): BelongsTo
    {
        return $this->belongsTo(Obras::class, 'obra_id');
    }

    public function construtora(): BelongsTo
    {
        return $this->belongsTo(Construtora::class, 'construtora_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function getArquivosPathsResolvedAttribute(): array
    {
        if (is_array($this->arquivos_paths) && $this->arquivos_paths !== []) {
            return array_values(array_filter($this->arquivos_paths));
        }

        return filled($this->arquivo_path) ? [$this->arquivo_path] : [];
    }

    public function getArquivosNomesResolvedAttribute(): array
    {
        if (is_array($this->arquivos_nomes) && $this->arquivos_nomes !== []) {
            return array_values(array_filter($this->arquivos_nomes));
        }

        if (filled($this->arquivo_nome)) {
            return [$this->arquivo_nome];
        }

        return filled($this->arquivo_path) ? [basename($this->arquivo_path)] : [];
    }

    public function hasArquivos(): bool
    {
        return $this->arquivos_paths_resolved !== [];
    }
}
