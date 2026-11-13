<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RoadDistanceService
{
    private const OSRM_TABLE_URL = 'https://router.project-osrm.org/table/v1/driving/';
    private const CACHE_TTL_SECONDS = 21600; // 6 jam

    /**
     * Hitung jarak mengikuti rute jalan raya (OSRM Table API) untuk koleksi faskes,
     * lalu urutkan berdasarkan jarak jalan raya terdekat.
     */
    public static function applyToCollection(Collection $items, float $originLat, float $originLng, bool $resort = true): Collection
    {
        if ($items->isEmpty()) {
            return $items;
        }

        $origLatKey = number_format($originLat, 4, '.', '');
        $origLngKey = number_format($originLng, 4, '.', '');

        $uncachedIndices = [];
        $uncachedCoords = [];
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

            if (! app()->runningUnitTests() && Cache::has($cacheKey)) {
                $resolvedDistances[$idx] = (float) Cache::get($cacheKey);
            } else {
                $uncachedIndices[] = [
                    'idx' => $idx,
                    'lat' => $lat,
                    'lng' => $lng,
                    'cache_key' => $cacheKey,
                ];
            }
        }

        // Panggil OSRM Table API secara batch (1 request untuk semua faskes sekaligus)
        if (! empty($uncachedIndices) && ! app()->runningUnitTests()) {
            foreach (array_chunk($uncachedIndices, 60) as $chunk) {
                $batchDistances = self::fetchOsrmBatch($originLat, $originLng, $chunk);
                foreach ($chunk as $pos => $entry) {
                    if (isset($batchDistances[$pos]) && $batchDistances[$pos] !== null) {
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
     * Request jarak jalan raya ke OSRM Table API menggunakan native cURL.
     *
     * @param  array<int, array{idx:int, lat:float, lng:float, cache_key:string}>  $destinations
     * @return array<int, float|null> Jarak dalam meter untuk tiap index tujuan
     */
    private static function fetchOsrmBatch(float $originLat, float $originLng, array $destinations): array
    {
        $coords = ["{$originLng},{$originLat}"];
        foreach ($destinations as $dest) {
            $coords[] = "{$dest['lng']},{$dest['lat']}";
        }

        $url = self::OSRM_TABLE_URL.implode(';', $coords).'?sources=0&annotations=distance';

        try {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT_MS => 2000,
                CURLOPT_TIMEOUT_MS => 3500,
                CURLOPT_USERAGENT => 'MediGO-Healthcare-Platform/1.0',
                CURLOPT_SSL_VERIFYPEER => false,
            ]);

            $raw = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($raw === false || $httpCode !== 200) {
                return [];
            }

            $data = json_decode($raw, true);
            if (($data['code'] ?? '') !== 'Ok' || ! isset($data['distances'][0]) || ! is_array($data['distances'][0])) {
                return [];
            }

            // Index 0 pada distances[0] adalah titik asal ke titik asal (0 m), tujuan mulai dari index 1
            $row = $data['distances'][0];
            $result = [];
            foreach (array_values($destinations) as $i => $dest) {
                $val = $row[$i + 1] ?? null;
                $result[$i] = (is_numeric($val) && $val > 0) ? (float) $val : null;
            }

            return $result;
        } catch (\Throwable $e) {
            Log::warning('OSRM batch distance failed: '.$e->getMessage());

            return [];
        }
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

