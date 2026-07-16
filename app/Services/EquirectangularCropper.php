<?php

namespace App\Services;

class EquirectangularCropper
{
    public function crop(
        string $sourcePath,
        float $yawDeg,
        float $pitchDeg,
        float $fovDeg,
        int $outputWidth = 1280,
        int $outputHeight = 960,
    ): string {
        @set_time_limit(120);

        $source = imagecreatefromstring(file_get_contents($sourcePath));

        if (! $source) {
            throw new \RuntimeException('Não foi possível ler a imagem equirretangular.');
        }

        if (! imageistruecolor($source)) {
            imagepalettetotruecolor($source);
        }

        $srcWidth = imagesx($source);
        $srcHeight = imagesy($source);

        $output = imagecreatetruecolor($outputWidth, $outputHeight);

        $yaw = deg2rad($yawDeg);
        $pitch = deg2rad($pitchDeg);
        $hFov = deg2rad($fovDeg);
        $vFov = 2 * atan(tan($hFov / 2) * $outputHeight / $outputWidth);

        $tanHalfH = tan($hFov / 2);
        $tanHalfV = tan($vFov / 2);

        $cosPitch = cos($pitch);
        $sinPitch = sin($pitch);
        $cosYaw = cos($yaw);
        $sinYaw = sin($yaw);

        for ($py = 0; $py < $outputHeight; $py++) {
            $ndcY = (1 - 2 * ($py + 0.5) / $outputHeight) * $tanHalfV;

            for ($px = 0; $px < $outputWidth; $px++) {
                $ndcX = (2 * ($px + 0.5) / $outputWidth - 1) * $tanHalfH;

                $len = sqrt($ndcX ** 2 + $ndcY ** 2 + 1);
                $dx = $ndcX / $len;
                $dy = $ndcY / $len;
                $dz = 1 / $len;

                $d1x = $dx;
                $d1y = $dy * $cosPitch - $dz * $sinPitch;
                $d1z = $dy * $sinPitch + $dz * $cosPitch;

                $d2x = $d1x * $cosYaw + $d1z * $sinYaw;
                $d2y = $d1y;
                $d2z = -$d1x * $sinYaw + $d1z * $cosYaw;

                $lon = atan2($d2x, $d2z);
                $lat = asin(max(-1, min(1, $d2y)));

                $srcX = ($lon / (2 * M_PI) + 0.5) * $srcWidth;
                $srcY = (0.5 - $lat / M_PI) * $srcHeight;

                $srcX = fmod(fmod($srcX, $srcWidth) + $srcWidth, $srcWidth);
                $srcY = max(0, min($srcHeight - 1, $srcY));

                imagesetpixel($output, $px, $py, $this->bilinearSample($source, $srcX, $srcY, $srcWidth, $srcHeight));
            }
        }

        imagedestroy($source);

        ob_start();
        imagejpeg($output, null, 90);
        $binary = ob_get_clean();

        imagedestroy($output);

        return $binary;
    }

    private function bilinearSample(\GdImage $image, float $x, float $y, int $width, int $height): int
    {
        $x0 = (int) floor($x);
        $y0 = (int) floor($y);
        $x1 = ($x0 + 1) % $width;
        $y1 = min($y0 + 1, $height - 1);

        $fx = $x - $x0;
        $fy = $y - $y0;

        $c00 = imagecolorat($image, $x0, $y0);
        $c10 = imagecolorat($image, $x1, $y0);
        $c01 = imagecolorat($image, $x0, $y1);
        $c11 = imagecolorat($image, $x1, $y1);

        $r = $this->blend($c00 >> 16 & 0xFF, $c10 >> 16 & 0xFF, $c01 >> 16 & 0xFF, $c11 >> 16 & 0xFF, $fx, $fy);
        $g = $this->blend($c00 >> 8 & 0xFF, $c10 >> 8 & 0xFF, $c01 >> 8 & 0xFF, $c11 >> 8 & 0xFF, $fx, $fy);
        $b = $this->blend($c00 & 0xFF, $c10 & 0xFF, $c01 & 0xFF, $c11 & 0xFF, $fx, $fy);

        return ($r << 16) | ($g << 8) | $b;
    }

    private function blend(int $v00, int $v10, int $v01, int $v11, float $fx, float $fy): int
    {
        $top = $v00 + ($v10 - $v00) * $fx;
        $bottom = $v01 + ($v11 - $v01) * $fx;

        return (int) round($top + ($bottom - $top) * $fy);
    }
}
