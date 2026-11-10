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
            if (str_contains($nameLower, 'laboratorium') || str_contains($nameLower, 'lab')) {
                return 'Laboratorium';
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

        // Ganti typo "JL," atau "JLN," menjadi "Jl."
        $alamat = preg_replace('/^(?:jl|jln),\s*/i', 'Jl. ', $alamat);
        // Hapus koma atau spasi di depan alamat
        $alamat = ltrim($alamat, ",- \t\n\r\0\x0B");

        if (empty($alamat) && !empty($kecamatan)) {
            $alamat = 'Kec. ' . $kecamatan;
        }

        // Jika alamat belum ada awalan nama jalan (Jl. / Gang dll) dan terlihat nama jalan, tambahkan "Jl. "
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
                array_filter([
                    'lat' => $lat,
                    'lng' => $lng,
                    'page' => $page,
                    'limit' => $limit,
                    'search' => $request->input('search'),
                    '_support_bpjs' => $request->input('_support_bpjs') ?? $request->input('bpjs'),
                    'jenis_faskes' => $request->input('jenis_faskes') ?? $request->input('jenis') ?? $request->input('tipe'),
                ], fn($val) => $val !== null && $val !== '')
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
                    $item = (array) $item;

                    // Menentukan jenis fasilitas kesehatan.
                    $jenisFaskes = $item['jenis_faskes']
                        ?? $item['tipe']
                        ?? $item['type']
                        ?? '';

                    $typeSlug = $item['type_slug']
                        ?? $this->getTypeSlug($jenisFaskes);

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
                        : (!empty($item['lat']) ? (float) $item['lat'] : null);

                    $itemLng = !empty($item['longitude'])
                        ? (float) $item['longitude']
                        : (!empty($item['lng']) ? (float) $item['lng'] : null);

                    // Nama faskes.
                    $rawName = $item['nama_faskes']
                        ?? $item['nama']
                        ?? $item['name']
                        ?? 'Fasilitas Kesehatan';

                    $formattedName = $this->formatFaskesName(
                        $rawName,
                        $typeSlug,
                        $jenisFaskes
                    );

                    // Alamat, kota, kecamatan.
                    $kota = $item['kota_kabupaten'] ?? $item['kota'] ?? $item['city'] ?? '';
                    $kecamatan = $item['kecamatan'] ?? $item['district'] ?? '';
                    $rawAlamat = $item['alamat_faskes']
                        ?? $item['alamat']
                        ?? $item['alamat_lengkap']
                        ?? $item['address']
                        ?? $item['jalan']
                        ?? $item['lokasi']
                        ?? '';

                    $cleanAddress = $this->formatAddress($rawAlamat, $kota, $kecamatan);

                    // Tipe faskes untuk badge (tidak boleh generic 'Fasilitas Kesehatan')
                    $resolvedType = $this->formatFaskesType($jenisFaskes, $typeSlug, $formattedName);

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
                            $rawAlamat
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

                        'type' => $resolvedType,

                        'type_slug' => $typeSlug,

                        'hospital_address' => $cleanAddress,

                        'hospital_rating' => $rating,

                        'hospital_class' => $item['kelas'] ?? $item['hospital_class'] ?? null,

                        'bpjs_support' => (bool) (
                            $item['_support_bpjs']
                            ?? $item['is_support_bpjs']
                            ?? $item['bpjs_support']
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
