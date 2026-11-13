<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RoadDistanceService
{
    private const CACHE_TTL_SECONDS = 21600; // 6 jam

    /** Ukuran batch tujuan per request OSRM. */
    private const BATCH_SIZE = 60;

    /** Lama (detik) OSRM dilewati setelah gagal/timeout, supaya request berikutnya tidak ikut lambat. */
    private const COOLDOWN_SECONDS = 60;

    private const COOLDOWN_KEY = 'osrm_cooldown';

    /**
     * Hitung jarak mengikuti rute jalan raya (OSRM Table API) untuk koleksi faskes,
     * lalu urutkan berdasarkan jarak jalan raya terdekat.
     *
     * Supaya respons tetap cepat:
     * - hanya N faskes terdekat (garis lurus) yang dihitung lewat OSRM (config services.osrm.max_items),
     * - semua panggilan OSRM dalam satu request dibatasi total waktunya (services.osrm.timeout),
     * - kalau OSRM gagal/timeout, OSRM dilewati sementara (cooldown) dan dipakai jarak garis lurus.
     */
    public static function applyToCollection(Collection $items, float $originLat, float $originLng, bool $resort = true): Collection
    {
        if ($items->isEmpty()) {
            return $items;
        }

        $origLatKey = number_format($originLat, 4, '.', '');
        $origLngKey = number_format($originLng, 4, '.', '');

        $candidates = [];
        $resolvedDistances = [];

        foreach ($items->values() as $idx => $item) {
            $lat = self::extractCoord($item, ['latitude', 'lat']);
            $lng = self::extractCoord($item, ['longitude', 'lng']);

            if ($lat === null || $lng === null) {
                continue;
            }

            $destLatKey = number_format($lat, 5, '.', '');
            $destLngKey = number_format($lng, 5, '.', '');
            $cacheKey = "osrm_road_km_{$origLatKey}_{$origLngKey}_{$destLatKey}_{$destLngKey}";

            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                $resolvedDistances[$idx] = (float) $cached;
            } else {
                $candidates[] = [
                    'idx' => $idx,
                    'lat' => $lat,
                    'lng' => $lng,
                    'cache_key' => $cacheKey,
                    'straight_km' => self::haversineKm($originLat, $originLng, $lat, $lng),
                ];
            }
        }

        if (! empty($candidates) && self::osrmAvailable()) {
            // Hanya faskes terdekat (garis lurus) yang dihitung lewat OSRM
            usort($candidates, fn ($a, $b) => $a['straight_km'] <=> $b['straight_km']);
            $candidates = array_slice($candidates, 0, max(0, (int) config('services.osrm.max_items', 20)));

            $deadline = microtime(true) + max(0.5, (float) config('services.osrm.timeout', 2.5));

            foreach (array_chunk($candidates, self::BATCH_SIZE) as $chunk) {
                $remaining = $deadline - microtime(true);
                if ($remaining < 0.3) {
                    break; // Waktu habis: sisanya pakai jarak garis lurus
                }

                $batchDistances = self::fetchOsrmBatch($originLat, $originLng, $chunk, $remaining);
                if ($batchDistances === null) {
                    break; // OSRM gagal, cooldown sudah dipasang
                }

                foreach ($chunk as $pos => $entry) {
                    if (isset($batchDistances[$pos])) {
                        $km = round($batchDistances[$pos] / 1000, 1);
                        $resolvedDistances[$entry['idx']] = $km;
                        Cache::put($entry['cache_key'], $km, self::CACHE_TTL_SECONDS);
                    }
                }
            }
        }

        $mapped = $items->values()->map(function ($item, $idx) use ($resolvedDistances, $originLat, $originLng) {
            $lat = self::extractCoord($item, ['latitude', 'lat']);
            $lng = self::extractCoord($item, ['longitude', 'lng']);

            $fallbackKm = null;
            if (is_object($item) && isset($item->distance_km)) {
                $fallbackKm = round((float) $item->distance_km, 1);
            } elseif (is_array($item) && isset($item['distance_km'])) {
                $fallbackKm = round((float) $item['distance_km'], 1);
            } elseif ($lat !== null && $lng !== null) {
                $fallbackKm = self::haversineKm($originLat, $originLng, $lat, $lng);
            }

            $finalKm = $resolvedDistances[$idx] ?? $fallbackKm;

            if ($finalKm !== null) {
                if (is_object($item)) {
                    $item->distance_km = $finalKm;
                    $item->jarak_km = $finalKm;
                    $item->jarak_teks = number_format($finalKm, 1, '.', '').' km';
                    $item->distance = number_format($finalKm, 1, '.', '').' km';
                } elseif (is_array($item)) {
                    $item['distance_km'] = $finalKm;
                    $item['jarak_km'] = $finalKm;
                    $item['jarak_teks'] = number_format($finalKm, 1, '.', '').' km';
                    $item['distance'] = number_format($finalKm, 1, '.', '').' km';
                }
            }

            return $item;
        });

        if ($resort) {
            return $mapped->sortBy(function ($item) {
                if (is_object($item)) {
                    return $item->distance_km ?? 999999;
                }

                return $item['distance_km'] ?? 999999;
            })->values();
        }

        return $mapped;
    }

    /**
     * OSRM aktif (config services.osrm.enabled; mati otomatis saat unit test) dan tidak sedang cooldown.
     */
    private static function osrmAvailable(): bool
    {
        return (bool) config('services.osrm.enabled', ! app()->runningUnitTests())
            && ! Cache::has(self::COOLDOWN_KEY);
    }

    /**
     * Request jarak jalan raya ke OSRM Table API.
     *
     * @param  array<int, array{idx:int, lat:float, lng:float, cache_key:string}>  $destinations
     * @return array<int, float>|null Jarak (meter) per index tujuan; null kalau OSRM gagal
     */
    private static function fetchOsrmBatch(float $originLat, float $originLng, array $destinations, float $timeoutSeconds): ?array
    {
        $coords = ["{$originLng},{$originLat}"];
        foreach ($destinations as $dest) {
            $coords[] = "{$dest['lng']},{$dest['lat']}";
        }

        $url = rtrim((string) config('services.osrm.url', 'https://router.project-osrm.org/table/v1/driving/'), '/').'/'
            .implode(';', $coords);

        try {
            $response = Http::withUserAgent('MediGO-Healthcare-Platform/1.0')
                ->connectTimeout(min(1.5, $timeoutSeconds))
                ->timeout($timeoutSeconds)
                ->get($url, ['sources' => 0, 'annotations' => 'distance']);

            $data = $response->json();
            if (! $response->successful() || ($data['code'] ?? '') !== 'Ok' || ! is_array($data['distances'][0] ?? null)) {
                self::startCooldown('HTTP '.$response->status());

                return null;
            }

            // Index 0 pada distances[0] adalah titik asal ke titik asal (0 m), tujuan mulai dari index 1
            $row = $data['distances'][0];
            $result = [];
            foreach (array_values($destinations) as $i => $dest) {
                $val = $row[$i + 1] ?? null;
                if (is_numeric($val) && $val > 0) {
                    $result[$i] = (float) $val;
                }
            }

            return $result;
        } catch (\Throwable $e) {
            self::startCooldown($e->getMessage());

            return null;
        }
    }

    private static function startCooldown(string $reason): void
    {
        Log::warning('OSRM distance unavailable, falling back to straight-line distance: '.$reason);
        Cache::put(self::COOLDOWN_KEY, true, self::COOLDOWN_SECONDS);
    }

    private static function extractCoord($item, array $keys): ?float
    {
        foreach ($keys as $k) {
            $val = is_object($item) ? ($item->{$k} ?? null) : ($item[$k] ?? null);
            if ($val !== null && $val !== '' && is_numeric($val)) {
                return (float) $val;
            }
        }

        return null;
    }

    public static function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $r = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);

        return round($r * 2 * atan2(sqrt($a), sqrt(1 - $a)), 1);
    }
}
