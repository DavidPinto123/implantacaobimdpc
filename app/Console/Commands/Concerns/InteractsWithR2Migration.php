<?php

namespace App\Console\Commands\Concerns;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait InteractsWithR2Migration
{
    protected function normalizeFiles(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter($value, fn ($item) => ! blank($item)));
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return array_values(array_filter($decoded, fn ($item) => ! blank($item)));
            }

            return trim($value) !== '' ? [$value] : [];
        }

        return [];
    }

    protected function extractPath(mixed $file): ?string
    {
        if (is_string($file)) {
            $path = parse_url($file, PHP_URL_PATH) ?: $file;

            return ltrim((string) $path, '/');
        }

        if (is_array($file)) {
            $candidate = $file['path'] ?? $file['url'] ?? $file[0] ?? null;

            if (! is_string($candidate) || trim($candidate) === '') {
                return null;
            }

            $path = parse_url($candidate, PHP_URL_PATH) ?: $candidate;

            return ltrim((string) $path, '/');
        }

        return null;
    }

    protected function resolvePublicPath(string $path): ?string
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');

        $candidates = [
            $path,
            'storage/'.$path,
        ];

        foreach ($candidates as $candidate) {
            $candidate = ltrim($candidate, '/');

            if (Storage::disk('public')->exists($candidate)) {
                return $candidate;
            }

            if (Str::startsWith($candidate, 'storage/')) {
                $withoutStorage = Str::after($candidate, 'storage/');

                if (Storage::disk('public')->exists($withoutStorage)) {
                    return $withoutStorage;
                }
            }
        }

        return null;
    }

    protected function resolveR2Path(string $path): ?string
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');

        $candidates = [$path];

        if (! Str::startsWith($path, 'storage/')) {
            $candidates[] = 'storage/'.$path;
        }

        foreach ($candidates as $candidate) {
            $candidate = ltrim($candidate, '/');

            if (Storage::disk('r2')->exists($candidate)) {
                return $candidate;
            }

            if (Str::startsWith($candidate, 'storage/')) {
                $withoutStorage = Str::after($candidate, 'storage/');

                if (Storage::disk('r2')->exists($withoutStorage)) {
                    return $withoutStorage;
                }
            }
        }

        return null;
    }

    protected function sanitizeFileName(string $oldPath, ?string $fallback = 'arquivo'): string
    {
        $fileName = basename(parse_url($oldPath, PHP_URL_PATH) ?: $oldPath);
        $fileName = urldecode($fileName);

        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $name = pathinfo($fileName, PATHINFO_FILENAME);
        $name = Str::slug($name);

        if ($name === '') {
            $name = $fallback ?: 'arquivo';
        }

        return $name.($extension ? '.'.strtolower($extension) : '');
    }

    protected function copyPublicFileToR2(string $oldPath, string $targetPath, bool $dryRun): array
    {
        $sourcePath = $this->resolvePublicPath($oldPath);

        if (! $sourcePath) {
            return [
                'status' => 'missing_source',
                'source' => null,
                'target' => $targetPath,
                'copied' => false,
                'source_disk' => null,
            ];
        }

        if (Storage::disk('r2')->exists($targetPath)) {
            return [
                'status' => 'already_exists',
                'source' => $sourcePath,
                'target' => $targetPath,
                'copied' => false,
                'source_disk' => 'public',
            ];
        }

        if ($dryRun) {
            return [
                'status' => 'dry_run',
                'source' => $sourcePath,
                'target' => $targetPath,
                'copied' => false,
                'source_disk' => 'public',
            ];
        }

        $stream = Storage::disk('public')->readStream($sourcePath);

        if ($stream === false) {
            return [
                'status' => 'stream_error',
                'source' => $sourcePath,
                'target' => $targetPath,
                'copied' => false,
                'source_disk' => 'public',
            ];
        }

        try {
            Storage::disk('r2')->writeStream($targetPath, $stream, ['visibility' => 'public']);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        if (! Storage::disk('r2')->exists($targetPath)) {
            return [
                'status' => 'validation_error',
                'source' => $sourcePath,
                'target' => $targetPath,
                'copied' => false,
                'source_disk' => 'public',
            ];
        }

        return [
            'status' => 'copied',
            'source' => $sourcePath,
            'target' => $targetPath,
            'copied' => true,
            'source_disk' => 'public',
        ];
    }

    protected function copyR2FileToR2(string $sourcePath, string $targetPath, bool $dryRun): array
    {
        $sourcePath = ltrim(str_replace('\\', '/', $sourcePath), '/');
        $targetPath = ltrim(str_replace('\\', '/', $targetPath), '/');

        if (! Storage::disk('r2')->exists($sourcePath)) {
            return [
                'status' => 'missing_source',
                'source' => $sourcePath,
                'target' => $targetPath,
                'copied' => false,
                'source_disk' => 'r2',
            ];
        }

        if (Storage::disk('r2')->exists($targetPath)) {
            return [
                'status' => 'already_exists',
                'source' => $sourcePath,
                'target' => $targetPath,
                'copied' => false,
                'source_disk' => 'r2',
            ];
        }

        if ($dryRun) {
            return [
                'status' => 'dry_run',
                'source' => $sourcePath,
                'target' => $targetPath,
                'copied' => false,
                'source_disk' => 'r2',
            ];
        }

        $stream = Storage::disk('r2')->readStream($sourcePath);

        if ($stream === false) {
            return [
                'status' => 'stream_error',
                'source' => $sourcePath,
                'target' => $targetPath,
                'copied' => false,
                'source_disk' => 'r2',
            ];
        }

        try {
            Storage::disk('r2')->writeStream($targetPath, $stream, ['visibility' => 'public']);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        if (! Storage::disk('r2')->exists($targetPath)) {
            return [
                'status' => 'validation_error',
                'source' => $sourcePath,
                'target' => $targetPath,
                'copied' => false,
                'source_disk' => 'r2',
            ];
        }

        return [
            'status' => 'copied',
            'source' => $sourcePath,
            'target' => $targetPath,
            'copied' => true,
            'source_disk' => 'r2',
        ];
    }

    protected function copyFileToR2PreferringPublic(string $oldPath, string $targetPath, bool $dryRun): array
    {
        if (Storage::disk('r2')->exists($targetPath)) {
            return [
                'status' => 'already_exists',
                'source' => null,
                'target' => $targetPath,
                'copied' => false,
                'source_disk' => null,
            ];
        }

        if ($this->resolvePublicPath($oldPath)) {
            return $this->copyPublicFileToR2($oldPath, $targetPath, $dryRun);
        }

        $r2SourcePath = $this->resolveR2Path($oldPath);

        if ($r2SourcePath) {
            return $this->copyR2FileToR2($r2SourcePath, $targetPath, $dryRun);
        }

        return [
            'status' => 'missing_everywhere',
            'source' => null,
            'target' => $targetPath,
            'copied' => false,
            'source_disk' => null,
        ];
    }

    protected function restoreOriginalFormat(mixed $originalValue, array $files): mixed
    {
        if (is_array($originalValue)) {
            return $files;
        }

        if (is_string($originalValue)) {
            $decoded = json_decode($originalValue, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $files;
            }

            return $files[0] ?? null;
        }

        return $files;
    }

    protected function initializeMigrationStats(): array
    {
        return [
            'records' => 0,
            'files_copied' => 0,
            'fields_updated' => 0,
            'warnings' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];
    }

    protected function printMigrationSummary(array $stats): void
    {
        $this->newLine();
        $this->info('Registros lidos: '.$stats['records']);
        $this->info('Arquivos copiados: '.$stats['files_copied']);
        $this->info('Campos atualizados: '.$stats['fields_updated']);
        $this->info('Avisos: '.$stats['warnings']);
        $this->info('Pulados: '.$stats['skipped']);
        $this->info('Erros: '.$stats['errors']);
    }
}
