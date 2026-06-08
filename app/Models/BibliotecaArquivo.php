<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

class BibliotecaArquivo extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'biblioteca_arquivos';

    protected $fillable = [
        'referenciavel_type',
        'referenciavel_id',
        'disco',
        'caminho',
        'nome_original',
        'mime_type',
        'tamanho',
        'uploaded_by',
    ];

    protected $casts = [
        'tamanho' => 'integer',
    ];

    public function referenciavel(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getUrlAttribute(): ?string
    {
        if (blank($this->caminho)) {
            return null;
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk($this->disco ?: 'r2');

        return $disk->url($this->caminho);
    }

    public function getIsImageAttribute(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }

    public function getIsPdfAttribute(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    public function deleteFromDisk(): void
    {
        if (blank($this->caminho)) {
            return;
        }

        Storage::disk($this->disco ?: 'r2')->delete($this->caminho);
    }
}
