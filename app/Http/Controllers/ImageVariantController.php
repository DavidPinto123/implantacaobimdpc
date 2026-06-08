<?php

namespace App\Http\Controllers;

use App\Support\ImageVariantUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\ImageManager;
use Symfony\Component\HttpFoundation\Response;

class ImageVariantController extends Controller
{
    public function __invoke(Request $request, string $sourceKey): Response
    {
        $width = $this->clamp($request->integer('w', 720), 80, 1920);
        $height = $request->query('h') !== null
            ? $this->clamp($request->integer('h'), 80, 1920)
            : null;
        $quality = $this->clamp($request->integer('q', 76), 45, 90);
        $fit = $request->query('fit') === 'cover' ? 'cover' : 'contain';

        $source = ImageVariantUrl::decodeSourceToken($sourceKey);

        abort_if($source === null, is_string($sourceKey) && str_contains($sourceKey, '.') ? 403 : 400);

        [$binary, $mimeType] = $this->fetchSource($source);

        abort_unless($binary !== '' && Str::startsWith($mimeType, 'image/'), 404);

        try {
            $manager = ImageManager::usingDriver(Driver::class);
            $image = $manager->decodeBinary($binary)->orient();

            if ($fit === 'cover' && $height !== null) {
                $image = $image->coverDown($width, $height);
            } else {
                $image = $image->scaleDown(width: $width, height: $height);
            }

            $encoded = $image->encode(new JpegEncoder(
                quality: $quality,
                progressive: true,
                strip: true
            ));
        } catch (\Throwable) {
            abort(404);
        }

        return response((string) $encoded, 200, [
            'Cache-Control' => 'public, max-age=604800, immutable',
            'Content-Type' => 'image/jpeg',
        ]);
    }

    /**
     * @param  array<string, string>  $source
     * @return array{0: string, 1: string}
     */
    private function fetchSource(array $source): array
    {
        if (($source['type'] ?? null) === 'storage') {
            $disk = (string) ($source['disk'] ?? config('filesystems.media_disk', 'r2'));
            $path = (string) ($source['path'] ?? '');

            try {
                if ($path === '' || ! Storage::disk($disk)->exists($path)) {
                    return ['', 'application/octet-stream'];
                }

                return [
                    Storage::disk($disk)->get($path) ?: '',
                    (string) (Storage::disk($disk)->mimeType($path) ?: 'image/jpeg'),
                ];
            } catch (\Throwable) {
                return ['', 'application/octet-stream'];
            }
        }

        if (($source['type'] ?? null) === 'remote') {
            $url = (string) ($source['url'] ?? '');

            if (! Str::startsWith($url, ['http://', 'https://'])) {
                return ['', 'application/octet-stream'];
            }

            $mirrorDisk = (string) config('filesystems.media_disk', 'r2');
            $host = (string) (parse_url($url, PHP_URL_HOST) ?? 'unknown');
            $path = ltrim((string) (parse_url($url, PHP_URL_PATH) ?? sha1($url)), '/');
            $mirrorPath = 'image-mirror/'.$host.'/'.$path;

            try {
                $mirrored = Storage::disk($mirrorDisk)->get($mirrorPath);
                if ($mirrored !== null && $mirrored !== '') {
                    return [$mirrored, 'image/jpeg'];
                }
            } catch (\Throwable) {
            }

            try {
                $response = Http::timeout(20)->retry(1, 300)->get($url);
            } catch (\Throwable) {
                return ['', 'application/octet-stream'];
            }

            if (! $response->successful()) {
                return ['', 'application/octet-stream'];
            }

            $binary = $response->body();
            $mimeType = Str::of((string) $response->header('Content-Type', 'image/jpeg'))
                ->before(';')
                ->trim()
                ->lower()
                ->toString();

            if ($binary !== '' && Str::startsWith($mimeType, 'image/')) {
                try {
                    Storage::disk($mirrorDisk)->put($mirrorPath, $binary);
                } catch (\Throwable) {
                }
            }

            return [$binary, $mimeType];
        }

        return ['', 'application/octet-stream'];
    }

    private function clamp(int $value, int $min, int $max): int
    {
        return max($min, min($max, $value));
    }
}
