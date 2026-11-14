<?php

namespace App\Support;

/**
 * Ikon & warna fallback per jenis faskes (dipakai bila faskes belum punya foto).
 * Versi JavaScript-nya ada di resources/js/utils/faskes-visual.js, jaga agar tetap sama.
 */
class FaskesVisual
{
    /** key => [ikon bootstrap-icons, label] */
    public const TYPES = [
        'rumah-sakit' => ['bi-hospital-fill', 'Rumah Sakit'],
        'puskesmas' => ['bi-house-heart-fill', 'Puskesmas'],
        'klinik' => ['bi-heart-pulse-fill', 'Klinik'],
        'apotek' => ['bi-capsule-pill', 'Apotek'],
        'laboratorium' => ['bi-eyedropper', 'Laboratorium'],
        'optik' => ['bi-eyeglasses', 'Optik'],
        'dokter-gigi' => ['bi-tooth', 'Dokter Gigi'],
        'dokter-umum' => ['bi-stethoscope', 'Dokter Umum'],
        'lainnya' => ['bi-plus-square-fill', 'Fasilitas Kesehatan'],
    ];

    /**
     * Tentukan key jenis dari teks jenis_faskes (prioritas) lalu nama faskes.
     */
    public static function key(?string $jenis, ?string $nama = null): string
    {
        $key = self::match(strtolower(trim((string) $jenis)));
        if ($key !== null) {
            return $key;
        }

        return self::match(strtolower(trim((string) $nama))) ?? 'lainnya';
    }

    public static function icon(?string $jenis, ?string $nama = null): string
    {
        return self::TYPES[self::key($jenis, $nama)][0];
    }

    /**
     * Pastikan URL thumbnail atau banner mengarah ke backend storage yang benar.
     */
    public static function normalizeStorageUrl(?string $url): ?string
    {
        if (empty($url)) {
            return null;
        }

        $beUrl = rtrim(config('services.medigo.url', env('MEDIGO_BE', 'http://127.0.0.1:8005')), '/');

        if (str_starts_with($url, '/storage') || str_starts_with($url, 'storage/')) {
            return $beUrl . '/' . ltrim($url, '/');
        }

        // Jika URL dari backend terlanjur memakai port 8000 (frontend)
        if (preg_match('#^https?://(?:localhost|127\.0\.0\.1):8000/storage/(.*)$#i', $url, $m)) {
            return "{$beUrl}/storage/{$m[1]}";
        }

        return $url;
    }

    private static function match(string $text): ?string
    {
        if ($text === '') {
            return null;
        }

        $rules = [
            'dokter-gigi' => ['dokter gigi', 'drg', 'dental', 'gigi'],
            'dokter-umum' => ['dokter umum', 'praktik dokter', 'praktek dokter', 'dokter'],
            'laboratorium' => ['laboratorium', 'lab ', 'lab.', 'prodia', 'pramita'],
            'apotek' => ['apotek', 'apotik', 'farmasi', 'pharmacy', 'kimia farma'],
            'optik' => ['optik', 'optical'],
            'puskesmas' => ['puskesmas', 'pkm'],
            'klinik' => ['klinik', 'clinic', 'balai pengobatan'],
            'rumah-sakit' => ['rumah sakit', 'hospital', 'rsud', 'rsu', 'rsia', 'rs '],
        ];

        foreach ($rules as $key => $needles) {
            foreach ($needles as $needle) {
                if (str_contains($text . ' ', $needle)) {
                    return $key;
                }
            }
        }

        // Singkatan RS di awal nama: RS, RSUD, RSPAD, RSCM, RSIA, ...
        return preg_match('/^rs[a-z]*\b/', $text) ? 'rumah-sakit' : null;
    }
}
