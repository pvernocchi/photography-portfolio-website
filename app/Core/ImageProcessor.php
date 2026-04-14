<?php
declare(strict_types=1);

namespace App\Core;

class ImageProcessor
{
    public static function processUpload(string $tmpFile, string $filename): array
    {
        $info = @getimagesize($tmpFile);
        if ($info === false || ($info['mime'] ?? '') !== 'image/jpeg') {
            throw new \RuntimeException('Only valid JPG images are allowed.');
        }

        $handle = fopen($tmpFile, 'rb');
        $magic = $handle ? fread($handle, 2) : '';
        if ($handle) {
            fclose($handle);
        }

        if ($magic !== "\xFF\xD8") {
            throw new \RuntimeException('Invalid JPEG file signature.');
        }

        $source = imagecreatefromjpeg($tmpFile);
        if ($source === false) {
            throw new \RuntimeException('Unable to read uploaded image.');
        }

        $width = (int) imagesx($source);
        $height = (int) imagesy($source);

        $baseDirs = [
            'originals' => BASE_PATH . '/storage/originals',
            'thumbnails' => BASE_PATH . '/storage/thumbnails',
            'display' => BASE_PATH . '/storage/display',
        ];

        foreach ($baseDirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }

        imagejpeg($source, $baseDirs['originals'] . '/' . $filename, 92);

        self::saveResized($source, $width, $height, 400, $baseDirs['thumbnails'] . '/' . $filename, 88);
        self::saveResized($source, $width, $height, 1600, $baseDirs['display'] . '/' . $filename, 85);

        imagedestroy($source);

        return [
            'width' => $width,
            'height' => $height,
            'file_size' => (int) filesize($baseDirs['originals'] . '/' . $filename),
        ];
    }

    public static function addWatermark($image, array $settings): void
    {
        $text = trim((string) ($settings['text'] ?? 'vernocchi.es'));
        if ($text === '') {
            return;
        }

        $position = (string) ($settings['position'] ?? 'bottom-right');
        $opacity = max(10, min(100, (int) ($settings['opacity'] ?? 30)));
        $fontSize = max(10, min(48, (int) ($settings['font_size'] ?? 16)));

        $width = imagesx($image);
        $height = imagesy($image);
        $color = imagecolorallocatealpha($image, 255, 255, 255, 127 - (int) round(($opacity / 100) * 127));

        $fontPath = BASE_PATH . '/public/assets/fonts/DejaVuSans.ttf';
        if (is_file($fontPath) && function_exists('imagettftext')) {
            $bbox = imagettfbbox($fontSize, 0, $fontPath, $text);
            $textW = abs((int) ($bbox[2] - $bbox[0]));
            $textH = abs((int) ($bbox[7] - $bbox[1]));
            $positions = self::watermarkPosition($position, $width, $height, $textW, $textH);

            if ($position === 'tiled') {
                for ($y = 60; $y < $height; $y += max(120, $textH + 60)) {
                    for ($x = 30; $x < $width; $x += max(220, $textW + 80)) {
                        imagettftext($image, $fontSize, -20, $x, $y, $color, $fontPath, $text);
                    }
                }
                return;
            }

            imagettftext($image, $fontSize, 0, $positions['x'], $positions['y'], $color, $fontPath, $text);
            return;
        }

        imagestring($image, 4, max(10, $width - 120), max(10, $height - 20), $text, $color);
    }

    public static function deleteImages(string $filename): void
    {
        foreach (['originals', 'thumbnails', 'display'] as $dir) {
            $path = BASE_PATH . '/storage/' . $dir . '/' . $filename;
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    private static function saveResized($source, int $width, int $height, int $targetWidth, string $path, int $quality): void
    {
        $newWidth = $width > $targetWidth ? $targetWidth : $width;
        $newHeight = (int) round(($height / max(1, $width)) * $newWidth);

        $dest = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($dest, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagejpeg($dest, $path, $quality);
        imagedestroy($dest);
    }

    private static function watermarkPosition(string $position, int $width, int $height, int $textW, int $textH): array
    {
        return match ($position) {
            'center' => ['x' => (int) (($width - $textW) / 2), 'y' => (int) (($height + $textH) / 2)],
            'bottom-left' => ['x' => 20, 'y' => $height - 20],
            default => ['x' => max(20, $width - $textW - 20), 'y' => $height - 20],
        };
    }
}
