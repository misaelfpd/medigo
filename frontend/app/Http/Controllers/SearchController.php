<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SearchController extends Controller
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
        $isPuskesmas = ($typeSlug === 'puskesmas') ||
            str_contains(strtolower($tipe ?? ''), 'puskesmas') ||
            str_contains(strtolower($name), 'puskesmas');

        if ($isPuskesmas && !preg_match('/^(puskesmas|pkm)\b/i', $name)) {
            return 'Puskesmas ' . $name;
        }

        return $name;
    }

    private function formatFaskesType($jenisFaskes, $typeSlug = '', $name = '')
    {
        $jenis = trim($jenisFaskes ?? '');

        if (empty($jenis) || strcasecmp($jenis, 'Fasilitas Kesehatan') === 0 || strcasecmp($jenis, 'faskes') === 0) {
            $nameLower = strtolower($name ?? '');
            $slugLower = strtolower($typeSlug ?? '');

            if ($slugLower === 'puskesmas' || str_contains($nameLower, 'puskesmas') || str_contains($nameLower, 'pkm')) {
                return 'Puskesmas';
            }
            if ($slugLower === 'clinic' || str_contains($nameLower, 'klinik') || str_contains($nameLower, 'clinic')) {
                return 'Klinik';
            }
            if (str_contains($nameLower, 'apotik') || str_contains($nameLower, 'apotek') || str_contains($nameLower, 'farma')) {
                return 'Apotek';
            }
            return 'Rumah Sakit';
        }

        $jenisLower = strtolower($jenis);
        if (str_contains($jenisLower, 'rumah sakit') || $jenisLower === 'rs') {
            return 'Rumah Sakit';
        }
        if (str_contains($jenisLower, 'puskesmas')) {
            return 'Puskesmas';
        }
        if (str_contains($jenisLower, 'klinik')) {
            return 'Klinik';
        }
        if (str_contains($jenisLower, 'apotik') || str_contains($jenisLower, 'apotek')) {
            return 'Apotek';
        }

        return ucwords($jenisLower);
    }

    private function formatAddress($alamat, $kota = '', $kecamatan = '')
    {
        $alamat = trim($alamat ?? '');
        $kota = trim($kota ?? '');
        $kecamatan = trim($kecamatan ?? '');

        $alamat = preg_replace('/^(?:jl|jln),\s*/i', 'Jl. ', $alamat);
        $alamat = ltrim($alamat, ",- \t\n\r\0\x0B");

        if (empty($alamat) && !empty($kecamatan)) {
            $alamat = 'Kec. ' . $kecamatan;
        }

        if (!empty($alamat) && !preg_match('/^(?:jl|jalan|gang|gg|komp|komplek|gedung|kav|kavling|dusun|desa|kel|kec)\b/i', $alamat)) {
            if (preg_match('/(?:raya|barat|timur|selatan|utara|no\b|nomor|km\b|blok)/i', $alamat)) {
                $alamat = 'Jl. ' . $alamat;
            }
        }

        if (!empty($alamat) && !empty($kota)) {
            if (str_ends_with(strtolower($alamat), strtolower($kota))) {
                return $alamat;
            }
            return $alamat . ', ' . $kota;
        } elseif (!empty($alamat)) {
            return $alamat;
        } elseif (!empty($kota)) {
            return $kota;
        }

        return '';
    }

    private function getGoogleMapsUrl($name, $lat = null, $lng = null, $kota = '', $alamat = '')
    {
        $parts = array_filter([trim($name), trim($alamat), trim($kota)]);
        $queryName = implode(', ', $parts);
        $encodedName = urlencode($queryName);

        if ($lat !== null && $lng !== null) {
            return "https://www.google.com/maps/search/{$encodedName}/@{$lat},{$lng},17z";
        }

        return "https://www.google.com/maps/search/?api=1&query={$encodedName}";
    }

    private function getBackendUrl()
    {
        $rawBackendUrl = config('services.medigo.url', env('MEDIGO_BE', 'http://127.0.0.1:8005'));
        return rtrim(str_replace('localhost', '127.0.0.1', $rawBackendUrl), '/');
    }

    /**
     * Tampilkan Halaman Pencarian Faskes
     */
    public function index(Request $request)
    {
        $backendUrl = $this->getBackendUrl();
        $options = [
            'kota' => [],
            'kelas' => [],
            'fasilitas' => [],
            'spesialisasi' => [],
        ];

        try {
            $res = Http::timeout(5)->get("{$backendUrl}/api/faskes/filter-options");
            if ($res->successful()) {
                $options = $res->json('data') ?? $options;
            }
        } catch (\Exception $e) {
            Log::warning('Failed fetching filter options: ' . $e->getMessage());
        }

        // Meneruskan parameter pencarian awal dari URL
        $initialQuery = [
            'q' => $request->input('q', ''),
            'kota' => $request->input('kota') ?? $request->input('kota_kabupaten', ''),
            'kelas' => $request->input('kelas', ''),
            'bpjs' => $request->has('bpjs') ? (string)$request->input('bpjs') : ($request->has('is_support_bpjs') ? (string)$request->input('is_support_bpjs') : ''),
            'fasilitas' => $request->input('fasilitas', ''),
            'spesialisasi' => $request->input('spesialisasi', ''),
        ];

        return view('users.search', compact('options', 'initialQuery'));
    }

    /**
     * Endpoint API AJAX untuk pencarian data faskes dinamis
     */
    public function searchData(Request $request)
    {
        $backendUrl = $this->getBackendUrl();

        $params = [
            'page' => $request->input('page', 1),
            'limit' => $request->input('limit', 24),
        ];

        if ($request->filled('q')) {
            $params['q'] = $request->input('q');
            $params['search'] = $request->input('q');
        }

        if ($request->filled('lat') && $request->filled('lng')) {
            $params['lat'] = $request->input('lat');
            $params['lng'] = $request->input('lng');
        }

        $kota = $request->input('kota') ?? $request->input('kota_kabupaten');
        if (!empty($kota)) {
            $params['kota_kabupaten'] = $kota;
            $params['kota'] = $kota;
        }

        if ($request->filled('kelas')) {
            $params['kelas'] = $request->input('kelas');
        }

        if ($request->has('bpjs') && $request->input('bpjs') !== '' && $request->input('bpjs') !== null) {
            $params['is_support_bpjs'] = $request->input('bpjs');
            $params['bpjs'] = $request->input('bpjs');
        }

        if ($request->filled('fasilitas')) {
            $params['fasilitas'] = $request->input('fasilitas');
        }

        if ($request->filled('spesialisasi')) {
            $params['spesialisasi'] = $request->input('spesialisasi');
        }

        try {
            $response = Http::timeout(8)->get("{$backendUrl}/api/faskes/search", $params);

            if ($response->successful()) {
                $data = $response->json();
                $items = $data['data'] ?? [];

                $formatted = array_map(function ($item) use ($request) {
                    $item = (array) $item;
                    $typeSlug = $item['type_slug'] ?? $this->getTypeSlug($item['jenis_faskes'] ?? '');
                    $formattedName = $this->formatFaskesName($item['nama_faskes'] ?? '', $typeSlug, $item['jenis_faskes'] ?? '');
                    $cleanAddress = $this->formatAddress($item['alamat_faskes'] ?? '', $item['kota_kabupaten'] ?? '', $item['kecamatan'] ?? '');
                    $displayType = $this->formatFaskesType($item['jenis_faskes'] ?? '', $typeSlug, $formattedName);

                    $lat = $item['latitude'] ?? null;
                    $lng = $item['longitude'] ?? null;
                    $mapsUrl = !empty($item['link_maps']) && $item['link_maps'] !== '#'
                        ? $item['link_maps']
                        : $this->getGoogleMapsUrl($formattedName, $lat, $lng, $item['kota_kabupaten'] ?? '', $cleanAddress);

                    $userLat = $request->input('lat');
                    $userLng = $request->input('lng');
                    $distanceStr = isset($item['distance_km']) && $item['distance_km'] !== null
                        ? number_format((float)$item['distance_km'], 1, ',', '.') . ' km'
                        : '-';

                    $detailUrl = route('faskes.detail', ['id' => $item['id_faskes'] ?? 0]);
                    if ($userLat && $userLng) {
                        $distParam = isset($item['distance_km']) && $item['distance_km'] !== null
                            ? '&distance=' . urlencode($distanceStr)
                            : '';
                        $detailUrl .= "?lat={$userLat}&lng={$userLng}{$distParam}";
                    }

                    return [
                        'id_faskes' => $item['id_faskes'] ?? 0,
                        'nama_faskes' => $formattedName,
                        'jenis_faskes' => $displayType,
                        'type_slug' => $typeSlug,
                        'kota_kabupaten' => $item['kota_kabupaten'] ?? '',
                        'alamat_faskes' => $cleanAddress,
                        'kelas' => $item['kelas'] ?? '',
                        'is_support_bpjs' => (bool) ($item['_support_bpjs'] ?? ($item['is_support_bpjs'] ?? false)),
                        'distance_km' => $item['distance_km'] ?? null,
                        'distance_str' => $distanceStr,
                        'latitude' => $lat,
                        'longitude' => $lng,
                        'maps_url' => $mapsUrl,
                        'detail_url' => $detailUrl,
                    ];
                }, $items);

                return response()->json([
                    'status' => 'success',
                    'total' => $data['total'] ?? count($formatted),
                    'page' => $data['page'] ?? 1,
                    'limit' => $data['limit'] ?? 24,
                    'has_more' => $data['has_more'] ?? false,
                    'data' => $formatted,
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil data dari server.',
                'data' => [],
            ], $response->status());

        } catch (\Exception $e) {
            Log::error('Search API request error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan koneksi sistem.',
                'data' => [],
            ], 500);
        }
    }
}

