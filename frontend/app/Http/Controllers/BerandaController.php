<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BerandaController extends Controller
{
    private function getTypeSlug($tipe)
    {
        $tipeLower = strtolower($tipe ?? '');

        if (
            str_contains($tipeLower, 'rumah sakit') ||
            str_contains($tipeLower, 'rs')
        ) {
            return 'hospital';
        } elseif (str_contains($tipeLower, 'puskesmas')) {
            return 'puskesmas';
        } elseif (
            str_contains($tipeLower, 'klinik') ||
            str_contains($tipeLower, 'dokter') ||
            str_contains($tipeLower, 'apotek')
        ) {
            return 'clinic';
        }

        return 'other';
    }

    private function formatFaskesName($name, $typeSlug, $tipe = '')
    {
        $name = trim($name ?? '');

        $isPuskesmas =
            ($typeSlug === 'puskesmas') ||
            str_contains(strtolower($tipe ?? ''), 'puskesmas') ||
            str_contains(strtolower($name), 'puskesmas');

        if (
            $isPuskesmas &&
            !preg_match('/^(puskesmas|pkm)\b/i', $name)
        ) {
            return 'Puskesmas ' . $name;
        }

        return $name;
    }

    private function getGoogleMapsUrl(
        $name,
        $lat = null,
        $lng = null,
        $kota = '',
        $alamat = ''
    ) {
        $parts = array_filter([
            trim($name),
            trim($alamat),
            trim($kota)
        ]);

        $queryName = implode(', ', $parts);
        $encodedName = urlencode($queryName);

        if ($lat !== null && $lng !== null) {
            return "https://www.google.com/maps/search/{$encodedName}/@{$lat},{$lng},17z";
        }

        return "https://www.google.com/maps/search/?api=1&query={$encodedName}";
    }

    private function getFacilityPhoto($typeSlug, $id = 0)
    {
        if ($typeSlug === 'hospital') {
            return ($id % 2 === 0)
                ? 'images/hospital_waterfront.jpg'
                : 'images/hospital.jpg';
        } elseif ($typeSlug === 'puskesmas') {
            return 'images/hospital.jpg';
        }

        return 'images/clinic.jpg';
    }

    public function index(Request $request)
    {
        $rawBackendUrl = config(
            'services.medigo.url',
            env('MEDIGO_BE', 'http://127.0.0.1:8005')
        );

        $backendUrl = rtrim(
            str_replace('localhost', '127.0.0.1', $rawBackendUrl),
            '/'
        );

        // Default data kosong, tanpa dummy data.
        $hospitals = [];
        $hasMore = false;

        try {
            $lat = $request->input('lat', -6.2088);
            $lng = $request->input('lng', 106.8220);
            $page = max(1, (int) $request->input('page', 1));
            $limit = max(1, (int) $request->input('limit', 12));

            // Request data faskes dari Backend API.
            $response = Http::timeout(8)->get(
                "{$backendUrl}/api/faskes",
                [
                    'lat' => $lat,
                    'lng' => $lng,
                    'page' => $page,
                    'limit' => $limit,
                    'search' => $request->input('search'),
                    'bpjs' => $request->input('bpjs'),
                    'jenis' => $request->input('jenis'),
                ]
            );

            if ($response->successful()) {
                $body = $response->json();

                $items = $body['data']['data']
                    ?? $body['data']
                    ?? [];

                // Pastikan response berupa array.
                if (!is_array($items)) {
                    $items = [];
                }

                $total = $body['data']['total'] ?? count($items);

                $hasMore = ($page * $limit) < $total;

                foreach ($items as $item) {
                    // Menentukan jenis fasilitas kesehatan.
                    $typeSlug = $item['type_slug']
                        ?? $this->getTypeSlug(
                            $item['jenis_faskes']
                            ?? $item['tipe']
                            ?? ''
                        );

                    // Rating.
                    $rating = (
                        !empty($item['avg_rating']) &&
                        (float) $item['avg_rating'] > 0
                    )
                        ? number_format(
                            (float) $item['avg_rating'],
                            1
                        )
                        : null;

                    // Koordinat.
                    $itemLat = !empty($item['latitude'])
                        ? (float) $item['latitude']
                        : null;

                    $itemLng = !empty($item['longitude'])
                        ? (float) $item['longitude']
                        : null;

                    // Nama faskes.
                    $rawName = $item['nama_faskes']
                        ?? 'Fasilitas Kesehatan';

                    $jenisFaskes = $item['jenis_faskes']
                        ?? $item['tipe']
                        ?? '';

                    $formattedName = $this->formatFaskesName(
                        $rawName,
                        $typeSlug,
                        $jenisFaskes
                    );

                    // Alamat dan kota.
                    $kota = $item['kota_kabupaten'] ?? '';

                    $alamat = $item['alamat_faskes']
                        ?? $item['alamat']
                        ?? '';

                    // Memeriksa link Google Maps dari backend.
                    $linkMaps = $item['link_maps'] ?? '';

                    $hasSpecificLink =
                        !empty($linkMaps) &&
                        !in_array(
                            $linkMaps,
                            ['-', 'TRUE', 'FALSE']
                        ) &&
                        str_starts_with($linkMaps, 'http') &&
                        (
                            str_contains($linkMaps, '@') ||
                            str_contains($linkMaps, 'place') ||
                            str_contains($linkMaps, 'maps.app.goo.gl') ||
                            str_contains($linkMaps, 'dir')
                        );

                    $navUrl = $hasSpecificLink
                        ? $linkMaps
                        : $this->getGoogleMapsUrl(
                            $formattedName,
                            $itemLat,
                            $itemLng,
                            $kota,
                            $alamat
                        );

                    $id = $item['id_faskes']
                        ?? $item['id']
                        ?? null;

                    // Menyusun data untuk kartu faskes.
                    $hospitals[] = [
                        'id' => $id,

                        'foto' => $this->getFacilityPhoto(
                            $typeSlug,
                            $id ?? 0
                        ),

                        'hospital_name' => $formattedName,

                        'type' => $jenisFaskes
                            ?: 'Fasilitas Kesehatan',

                        'type_slug' => $typeSlug,

                        'hospital_address' => $alamat .
                            (!empty($kota) ? ', ' . $kota : ''),

                        'hospital_rating' => $rating,

                        'hospital_class' => $item['kelas'] ?? null,

                        'bpjs_support' => (bool) (
                            $item['_support_bpjs']
                            ?? $item['is_support_bpjs']
                            ?? false
                        ),

                        'distance' => isset($item['distance_km'])
                            ? number_format(
                                (float) $item['distance_km'],
                                1
                            ) . ' km'
                            : '2.4 km',

                        'is_open' => true,

                        'lat' => $itemLat,

                        'lng' => $itemLng,

                        'navigasi_url' => $navUrl,

                        'detail_url' => url('/faskes/' . $id),
                    ];
                }
            } else {
                Log::warning(
                    'Backend API returned unsuccessful response: ' .
                    $response->status()
                );
            }
        } catch (\Exception $e) {
            Log::warning(
                'Failed to fetch faskes from backend API: ' .
                $e->getMessage()
            );
        }

        // Response JSON untuk AJAX.
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'items' => $hospitals,
                    'page' => $page ?? 1,
                    'has_more' => $hasMore,
                ]
            ]);
        }

        return view(
            'users.faskes',
            compact('hospitals', 'hasMore')
        );
    }

    /**
     * AJAX endpoint to load more faskes for Beranda.
     */
    public function loadMore(Request $request)
    {
        return $this->index($request);
    }
}
