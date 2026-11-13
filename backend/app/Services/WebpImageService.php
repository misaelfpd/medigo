<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Konversi gambar upload (JPG, PNG, GIF, WebP, BMP) menjadi WebP memakai ekstensi GD bawaan PHP.
 * Gambar diperkecil kalau melebihi ukuran maksimal (rasio tetap, tidak di-crop),
 * jadi frontend bebas mengatur tampilannya (object-fit: cover, dst).
 */
class WebpImageService
{
    /** Ukuran maksimal (lebar x tinggi) per tipe foto. */
    public const MAX_SIZE = [
        'thumbnail' => [800, 800],
        'banner' => [1920, 1080],
        'galeri' => [1600, 1600],
    ];

    public const QUALITY = 80;

    public static function isSupported(): bool
    {
        return function_exists('imagewebp') && function_exists('imagecreatefromstring');
    }

    /**
     * Simpan file upload sebagai WebP.
     *
     * @return array{path: string, lebar: int, tinggi: int, ukuran_bytes: int}
     */
    public function storeUpload(UploadedFile $file, string $directory, string $tipe): array
    {
        $binary = file_get_contents($file->getRealPath());
        if ($binary === false) {
            throw new RuntimeException('File gambar tidak bisa dibaca.');
        }

        return $this->storeBinary($binary, $directory, $tipe, $file->getRealPath());
    }

    /**
     * Buat salinan WebP dari file yang sudah ada di disk public (misal foto galeri -> thumbnail).
     *
     * @return array{path: string, lebar: int, tinggi: int, ukuran_bytes: int}
     */
    public function copyFromPublicPath(string $sourcePath, string $directory, string $tipe): array
    {
        $disk = Storage::disk('public');
        if (! $disk->exists($sourcePath)) {
            throw new RuntimeException('File foto sumber tidak ditemukan di server.');
        }

        return $this->storeBinary($disk->get($sourcePath), $directory, $tipe);
    }

    public function delete(?string $path): void
    {
        if (! empty($path) && ! str_contains($path, '..')) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * @return array{path: string, lebar: int, tinggi: int, ukuran_bytes: int}
     */
    private function storeBinary(string $binary, string $directory, string $tipe, ?string $realPath = null): array
    {
        if (! self::isSupported()) {
            throw new RuntimeException('Server belum mendukung konversi WebP. Aktifkan ekstensi GD (extension=gd) di php.ini.');
        }

        $image = @imagecreatefromstring($binary);
        if ($image === false) {
            throw new RuntimeException('File bukan gambar yang valid atau formatnya tidak didukung.');
        }

        try {
            $image = $this->fixOrientation($image, $realPath);
            [$maxW, $maxH] = self::MAX_SIZE[$tipe] ?? self::MAX_SIZE['galeri'];
            $image = $this->resizeToFit($image, $maxW, $maxH);

            // GD hanya bisa menyimpan WebP dari truecolor image (PNG/GIF palette harus dikonversi)
            if (! imageistruecolor($image)) {
                imagepalettetotruecolor($image);
            }
            imagealphablending($image, false);
            imagesavealpha($image, true);

            ob_start();
            $ok = imagewebp($image, null, self::QUALITY);
            $webp = ob_get_clean();

            if (! $ok || $webp === false || $webp === '') {
                throw new RuntimeException('Gagal mengonversi gambar ke WebP.');
            }

            $width = imagesx($image);
            $height = imagesy($image);
        } finally {
            imagedestroy($image);
        }

        $path = trim($directory, '/').'/'.$tipe.'-'.Str::lower(Str::random(20)).'.webp';
        Storage::disk('public')->put($path, $webp);

        return [
            'path' => $path,
            'lebar' => $width,
            'tinggi' => $height,
            'ukuran_bytes' => strlen($webp),
        ];
    }

    private function resizeToFit(\GdImage $image, int $maxW, int $maxH): \GdImage
    {
        $w = imagesx($image);
        $h = imagesy($image);
        $scale = min($maxW / $w, $maxH / $h, 1);

        if ($scale >= 1) {
            return $image;
        }

        $newW = max(1, (int) round($w * $scale));
        $newH = max(1, (int) round($h * $scale));

        $resized = imagecreatetruecolor($newW, $newH);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagefill($resized, 0, 0, imagecolorallocatealpha($resized, 0, 0, 0, 127));
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newW, $newH, $w, $h);
        imagedestroy($image);

        return $resized;
    }

    /**
     * Foto dari HP sering tersimpan miring dengan info rotasi di EXIF. Putar sesuai EXIF
     * supaya hasil WebP (yang tidak menyimpan EXIF) tetap tegak.
     */
    private function fixOrientation(\GdImage $image, ?string $realPath): \GdImage
    {
        if (! $realPath || ! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($realPath);
        $orientation = (int) ($exif['Orientation'] ?? 1);

        $angle = match ($orientation) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($angle === 0) {
            return $image;
        }

        $rotated = imagerotate($image, $angle, 0);
        if ($rotated === false) {
            return $image;
        }
        imagedestroy($image);

        return $rotated;
    }
}
