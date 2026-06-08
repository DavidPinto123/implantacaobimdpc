<?php

namespace App\Traits;

use App\Models\BibliotecaArquivo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait TemArquivos
{
    public function arquivos(): MorphMany
    {
        return $this->morphMany(BibliotecaArquivo::class, 'referenciavel');
    }

    public function anexarArquivo(UploadedFile $file, ?int $uploadedBy = null, string $disco = 'r2', ?string $pasta = null): BibliotecaArquivo
    {
        $pasta = $pasta ?: $this->bibliotecaArquivosPasta();
        $nome = Str::uuid().'.'.$file->getClientOriginalExtension();
        $caminho = $file->storeAs($pasta, $nome, $disco);

        return $this->arquivos()->create([
            'disco' => $disco,
            'caminho' => $caminho,
            'nome_original' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'tamanho' => $file->getSize(),
            'uploaded_by' => $uploadedBy ?? auth()->id(),
        ]);
    }

    public function removerArquivo(int $arquivoId): bool
    {
        $arquivo = $this->arquivos()->find($arquivoId);

        if (! $arquivo) {
            return false;
        }

        Storage::disk($arquivo->disco ?: 'r2')->delete($arquivo->caminho);
        $arquivo->delete();

        return true;
    }

    protected function bibliotecaArquivosPasta(): string
    {
        $base = Str::slug(class_basename($this));

        return "biblioteca-arquivos/{$base}/{$this->getKey()}";
    }
}
