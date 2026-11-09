<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MapController extends Controller
{
    private function getTypeSlug($tipe)
    {
        $tipeLower = strtolower($tipe ?? '');
        if (str_contains($tipeLower, 'rumah sakit') || str_contains($tipeLower, 'rs')) {
            return 'hospital';
        } elseif (str_contains($tipeLower, 'puskesmas')) {
            return 'puskesmas';
        } elseif (str_contains($tipeLower, 'klinik') || str_contains($tipeLower, 'dokter') || str_contains($tipeLower, 'apotek')) {
            return 'clinic';
        }
        return 'other';
    }

    private function formatFaskesName($name, $typeSlug, $tipe = '')
    {
        $name = trim($name ?? '');
        $isPuskesmas = ($typeSlug === 'puskesmas') || str_contains(strtolower($tipe ?? ''), 'puskesmas') || str_contains(strtolower($name), 'puskesmas');
        if ($isPuskesmas && !preg_match('/^(puskesmas|pkm)\b/i', $name)) {
            return 'Puskesmas ' . $name;
        }
        return $name;
    }

    private function getGoogleMapsUrl($name, $lat = null, $lng = null, $kota = '', $alamat = '')
    {
        $parts = array_filter([trim($name), trim($alamat), trim($kota)]);
        $queryName = implode(', ', $parts);
        $encodedName = urlencode($queryName);

        if ($lat && $lng) {
            return "https://www.google.com/maps/search/{$encodedName}/@{$lat},{$lng},17z";
        }

        return "https://www.google.com/maps/search/?api=1&query={$encodedName}";
    }

    private function getFacilityPhoto($typeSlug, $id = 0)
    {
        if ($typeSlug === 'hospital') {
            return ($id % 2 === 0) ? 'images/hospital_waterfront.jpg' : 'images/hospital.jpg';
        } elseif ($typeSlug === 'puskesmas') {
            return 'images/hospital.jpg';
        }
        return 'images/clinic.jpg';
    }

    public function index(Request $request)
    {
        $rawBackendUrl = config('services.medigo.url', env('MEDIGO_BE', 'http://127.0.0.1:8005'));
        $backendUrl = rtrim(str_replace('localhost', '127.0.0.1', $rawBackendUrl), '/');
        $faskesList = [];

        try {
            $userLat = $request->input('lat', -6.2088);
            $userLng = $request->input('lng', 106.8220);

            // Fetch faskes that have coordinates from the backend API, sorted by distance from user position (limit 30)
            $response = Http::timeout(8)->get("{$backendUrl}/api/faskes", [
                'with_coords' => 1,
                'lat' => $userLat,
                'lng' => $userLng,
                'limit' => 30,
                'search' => $request->input('search'),
                'bpjs' => $request->input('bpjs'),
                'jenis' => $request->input('jenis'),
            ]);

            if ($response->successful()) {
                $body = $response->json();
                $items = $body['data']['data'] ?? ($body['data'] ?? []);

                foreach ($items as $item) {
                    $lat = !empty($item['latitude']) ? (float)$item['latitude'] : null;
                    $lng = !empty($item['longitude']) ? (float)$item['longitude'] : null;

                    // Ensure item has valid coordinates for map placement
                    if ($lat === null || $lng === null) {
                        continue;
                    }

                    // UPDATE: BE menggunakan jenis_faskes, bukan tipe
                    $typeSlug = $item['type_slug'] ?? $this->getTypeSlug($item['jenis_faskes'] ?? $item['tipe'] ?? '');
                    $rating = (!empty($item['avg_rating']) && (float)$item['avg_rating'] > 0) 
                        ? number_format((float)$item['avg_rating'], 1) 
                        : null;

                    $reviewsCount = !empty($item['total_reviews']) && (int)$item['total_reviews'] > 0
                        ? (int)$item['total_reviews']
                        : 0;

                    $rawName = $item['nama_faskes'] ?? $item['name'] ?? 'Fasilitas Kesehatan';
                    // UPDATE: BE menggunakan jenis_faskes, bukan tipe
                    $jenisFaskes = $item['jenis_faskes'] ?? $item['tipe'] ?? '';
                    $formattedName = $this->formatFaskesName($rawName, $typeSlug, $jenisFaskes);
                    $kota = $item['kota_kabupaten'] ?? '';

                    $hasSpecificLink = !empty($item['link_maps']) 
                        && !in_array($item['link_maps'], ['-', 'TRUE', 'FALSE']) 
                        && str_starts_with($item['link_maps'], 'http')
                        && (str_contains($item['link_maps'], '@') || str_contains($item['link_maps'], 'place') || str_contains($item['link_maps'], 'maps.app.goo.gl') || str_contains($item['link_maps'], 'dir'));

                    // UPDATE: BE menggunakan alamat_faskes, bukan alamat
                    $alamat = $item['alamat_faskes'] ?? $item['alamat'] ?? '';
                    $navUrl = $hasSpecificLink
                        ? $item['link_maps']
                        : $this->getGoogleMapsUrl($formattedName, $lat, $lng, $kota, $alamat);

                    $faskesList[] = [
                        'id' => $item['id_faskes'] ?? $item['id'],
                        'name' => $formattedName,
                        'type' => $jenisFaskes ?: 'Fasilitas Kesehatan',
                        'type_slug' => $typeSlug,
                        // UPDATE: BE menggunakan alamat_faskes, bukan alamat
                        'address' => $alamat . (!empty($kota) ? ', ' . $kota : ''),
                        'rating' => $rating,
                        'total_reviews' => $reviewsCount,
                        'hospital_class' => $item['kelas'] ?? null,
                        // UPDATE: BE menggunakan _support_bpjs, bukan is_support_bpjs
                        'bpjs_support' => (bool) ($item['_support_bpjs'] ?? $item['is_support_bpjs'] ?? false),
                        'distance' => isset($item['distance_km']) ? number_format($item['distance_km'], 1) . ' km' : null,
                        // UPDATE: BE menggunakan kontak, bukan telepon
                        'phone' => !empty($item['kontak']) ? $item['kontak'] : (!empty($item['telp_faskes']) ? $item['telp_faskes'] : null),
                        'lat' => $lat,
                        'lng' => $lng,
                        'foto' => $this->getFacilityPhoto($typeSlug, $item['id_faskes'] ?? $item['id']),
                        'navigasi_url' => $navUrl,
                        'detail_url' => url('/faskes/' . ($item['id_faskes'] ?? $item['id'])),
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to fetch map faskes from backend API: ' . $e->getMessage());
        }

        // Return whatever facilities were retrieved (or empty if offline)
        return view('users.map', compact('faskesList'));
    }
}

