<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class AtaAnexo extends Model
{
    protected $table = 'ata_anexos';

    protected $fillable = ['ata_id', 'tema_id', 'nome_original', 'caminho', 'mime_type', 'tamanho', 'ordem'];

    public function ata(): BelongsTo
    {
        return $this->belongsTo(Ata::class);
    }

    public function tema(): BelongsTo
    {
        return $this->belongsTo(AtaTema::class, 'tema_id');
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->caminho);
    }

    public function absolutePath(): string
    {
        return Storage::disk('public')->path($this->caminho);
    }

    public function tamanhoFormatado(): string
    {
        $bytes = $this->tamanho ?? 0;
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }
}
