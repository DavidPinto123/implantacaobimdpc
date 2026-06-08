<?php

namespace App\Support;

use Illuminate\Support\Str;

class ImageVariantUrl
{
    public static function forRemote(
        ?string $url,
        int $width = 720,
        ?int $height = null,
        string $fit = 'contain',
        int $quality = 76
    ): ?string {
        if (! filled($url) || ! Str::startsWith((string) $url, ['http://', 'https://'])) {
            return $url;
        }

        return self::build(
            [
                'type' => 'remote',
                'url' => (string) $url,
            ],
            $width,
            $height,
            $fit,
            $quality
        );
    }

    public static function forStorage(
        ?string $disk,
        ?string $path,
        int $width = 720,
        ?int $height = null,
        string $fit = 'contain',
        int $quality = 76
    ): ?string {
        if (! filled($path)) {
            return null;
        }

        if (Str::startsWith((string) $path, ['http://', 'https://'])) {
            return self::forRemote((string) $path, $width, $height, $fit, $quality);
        }

        return self::build(
            [
                'type' => 'storage',
                'disk' => $disk ?: (string) config('filesystems.media_disk', 'r2'),
                'path' => (string) $path,
            ],
            $width,
            $height,
            $fit,
            $quality
        );
    }

    public static function decodeSourceToken(string $token): ?array
    {
        $parts = explode('.', $token, 2);

        if (count($parts) !== 2) {
            return null;
        }

        [$payload, $mac] = $parts;

        $expected = hash_hmac('sha256', $payload, (string) config('app.key'));

        if (! hash_equals($expected, $mac)) {
            return null;
        }

        $json = base64_decode(strtr($payload, '-_', '+/'), true);

        if ($json === false) {
            return null;
        }

        $source = json_decode($json, true);

        return is_array($source) ? $source : null;
    }

    /**
     * @param  array<string, string>  $source
     */
    private static function build(
        array $source,
        int $width,
        ?int $height,
        string $fit,
        int $quality
    ): string {
        $json = json_encode($source, JSON_UNESCAPED_SLASHES);
        $payload = strtr(base64_encode($json), '+/', '-_');
        $mac = hash_hmac('sha256', $payload, (string) config('app.key'));
        $token = $payload.'.'.$mac;

        return route('media.image-variants.show', array_filter([
            'sourceKey' => $token,
            'w' => $width,
            'h' => $height,
            'fit' => $fit,
            'q' => $quality,
        ], fn ($value) => $value !== null), false);
    }
}
