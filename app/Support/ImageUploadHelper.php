<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ImageUploadHelper
{
    public static function save(
        TemporaryUploadedFile $file,
        ?string $disk = null,
        string $directory = '',
        ?string $field = null
    ): string {
        $resolvedDisk = $disk ?? (string) config('filesystems.media_disk', 'r2');

        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getMimeType() ?: 'application/octet-stream';
        $hash = Str::lower(Str::random(8));

        $typePrefix = match (true) {
            str_starts_with($mimeType, 'image/') => 'img',
            $mimeType === 'application/pdf' => 'pdf',
            str_starts_with($mimeType, 'video/') => 'video',
            default => 'arquivo',
        };

        $fieldPrefix = $field ? Str::slug($field, '-') : 'arquivo';

        $filename = "{$typePrefix}-{$fieldPrefix}-{$hash}.{$extension}";

        $path = trim($directory, '/');
        $path = ($path !== '' ? $path.'/' : '').$filename;

        if (in_array($mimeType, ['image/jpeg', 'image/png'], true)) {
            $manager = ImageManager::usingDriver(Driver::class);

            $sourcePath = $file->getRealPath();
            $tempPath = storage_path('app/tmp/'.$filename);

            if (! is_dir(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0775, true);
            }

            $image = $manager->decodePath($sourcePath)->orient();
            $image->save($tempPath);

            Storage::disk($resolvedDisk)->put(
                $path,
                file_get_contents($tempPath),
                [
                    'visibility' => 'public',
                    'ContentType' => $mimeType,
                ]
            );

            @unlink($tempPath);

            return $path;
        }

        Storage::disk($resolvedDisk)->putFileAs(
            trim($directory, '/'),
            $file,
            $filename,
            [
                'visibility' => 'public',
                'ContentType' => $mimeType,
            ]
        );
        return $path;
    }

    public static function callback(
        string|Closure $directory,
        ?string $disk = null,
        ?string $field = null
    ): Closure {
        return function ($file, $get, $record = null) use ($directory, $disk, $field) {
            $resolvedDirectory = $directory instanceof Closure
                ? $directory($get, $record)
                : $directory;

            return self::save($file, $disk, $resolvedDirectory, $field);
        };
    }
}
