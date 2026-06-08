<?php

namespace App\Support;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PdfMedia
{
    protected static function diskName(): string
    {
        return (string) config('filesystems.media_disk', 'r2');
    }

    public static function filePath($file): ?string
    {
        if (is_string($file)) {
            return $file;
        }

        if (is_array($file)) {
            return $file['path'] ?? $file['url'] ?? $file[0] ?? null;
        }

        return null;
    }

    public static function src($file): ?string
    {
        $path = self::filePath($file);

        if (! $path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', 'file://'])) {
            return $path;
        }

        $path = ltrim(str_replace('\\', '/', $path), '/');

        $candidates = [
            public_path($path),
            public_path('storage/'.$path),
            storage_path('app/public/'.$path),
            $path,
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        try {
            /** @var FilesystemAdapter $disk */
            $disk = Storage::disk(static::diskName());

            if ($disk->exists($path)) {
                return $disk->url($path);
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }

    public static function href($file): ?string
    {
        $path = self::filePath($file);

        if (! $path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        try {
            /** @var FilesystemAdapter $disk */
            $disk = Storage::disk(static::diskName());

            if ($disk->exists($path)) {
                return $disk->url($path);
            }
        } catch (\Throwable $e) {
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk(static::diskName());

        return $disk->url($path);
    }

    public static function extension($file): string
    {
        $path = self::filePath($file);

        if (! $path) {
            return '';
        }

        return strtolower(pathinfo($path, PATHINFO_EXTENSION));
    }

    public static function isVideo($file): bool
    {
        return in_array(self::extension($file), ['mp4', 'mov', 'avi', 'mpeg', 'webm'], true);
    }

    public static function normalizeFiles($files, int $limit = 6): array
    {
        if (empty($files)) {
            return [];
        }

        if (! is_array($files)) {
            $decoded = json_decode($files, true);
            $files = is_array($decoded) ? $decoded : [$files];
        }

        return array_slice(array_values(array_filter($files)), 0, $limit);
    }
}
