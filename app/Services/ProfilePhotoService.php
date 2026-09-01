<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class ProfilePhotoService
{
    public function store(UploadedFile $file, int $userId): string
    {
        if (! extension_loaded('gd') || ! function_exists('imagewebp')) {
            throw new \RuntimeException('GD/WebP no disponible en el servidor.');
        }

        $source = $this->createImageResource($file);
        if (! $source) {
            throw new \RuntimeException('No se pudo procesar la imagen subida.');
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $squareSize = min($sourceWidth, $sourceHeight);
        $sourceX = (int) floor(($sourceWidth - $squareSize) / 2);
        $sourceY = (int) floor(($sourceHeight - $squareSize) / 2);

        $canvas = imagecreatetruecolor(350, 350);
        imagealphablending($canvas, true);
        imagesavealpha($canvas, true);

        imagecopyresampled(
            $canvas,
            $source,
            0,
            0,
            $sourceX,
            $sourceY,
            350,
            350,
            $squareSize,
            $squareSize
        );

        $directory = public_path('img/photo_profile');
        if (! is_dir($directory) && ! @mkdir($directory, 0755, true) && ! is_dir($directory)) {
            imagedestroy($canvas);
            imagedestroy($source);
            throw new \RuntimeException('No se pudo crear el directorio de fotos de perfil.');
        }

        if (! is_writable($directory)) {
            imagedestroy($canvas);
            imagedestroy($source);
            throw new \RuntimeException('El directorio de fotos de perfil no tiene permisos de escritura.');
        }

        $filename = 'user_'.$userId.'_'.Str::random(12).'.webp';
        $saved = imagewebp($canvas, $directory.DIRECTORY_SEPARATOR.$filename, 82);

        imagedestroy($canvas);
        imagedestroy($source);

        if (! $saved) {
            throw new \RuntimeException('No se pudo guardar la imagen en formato WebP.');
        }

        return $filename;
    }

    public function delete(?string $filename): void
    {
        $filename = trim((string) $filename);
        if ($filename === '' || basename($filename) !== $filename) {
            return;
        }

        $path = public_path('img/photo_profile/'.$filename);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private function createImageResource(UploadedFile $file): \GdImage|false
    {
        $mime = (string) $file->getMimeType();
        $path = $file->getRealPath();

        if (! $path) {
            return false;
        }

        return match ($mime) {
            'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default => false,
        };
    }
}
