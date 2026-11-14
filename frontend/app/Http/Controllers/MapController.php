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
        } elseif (str_contains($tipeLower, 'klinik')) {
            return 'klinik';
        } elseif (str_contains($tipeLower, 'apotek') || str_contains($tipeLower, 'apotik')) {
            return 'apotek';
        } elseif (str_contains($tipeLower, 'laboratorium') || str_contains($tipeLower, 'lab')) {
            return 'laboratorium';
        } elseif (str_contains($tipeLower, 'optik')) {
            return 'optik';
        } elseif (str_contains($tipeLower, 'dokter gigi')) {
            return 'dokter-gigi';
        } elseif (str_contains($tipeLower, 'dokter')) {
            return 'dokter-umum';
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

            return $jenisFaskes ?: 'Fasilitas Kesehatan';
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

        if ($lat && $lng) {
            return "https://www.google.com/maps/search/{$encodedName}/@{$lat},{$lng},17z";
        }

        return "https://www.google.com/maps/search/?api=1&query={$encodedName}";
    }

    public function index(Request $request)
    {
        $rawBackendUrl = config('services.medigo.url', env('MEDIGO_BE', 'http://127.0.0.1:8005'));
        $backendUrl = rtrim(str_replace('localhost', '127.0.0.1', $rawBackendUrl), '/');
        $faskesList = [];
        $errorMessage = null;

        try {
            $typeParam = $request->input('type') ?? $request->input('jenis') ?? $request->input('jenis_faskes');
            $queryParams = [
                'with_coords' => 1,
                'limit' => 100,
                'search' => $request->input('search'),
                'bpjs' => $request->input('bpjs'),
                'type' => $typeParam,
            ];

            // Hanya sertakan koordinat jika dikirimkan oleh browser/client (tanpa hardcode lokasi default)
            if ($request->filled('lat') && $request->filled('lng')) {
                $queryParams['lat'] = (float) $request->input('lat');
                $queryParams['lng'] = (float) $request->input('lng');
            }

            // Fetch faskes that have coordinates from the backend API
            $response = Http::timeout(8)->get("{$backendUrl}/api/faskes", array_filter($queryParams, fn($v) => !is_null($v) && $v !== ''));

            if ($response->successful()) {
                $body = $response->json();
                $items = $body['data']['data'] ?? ($body['data'] ?? []);

                foreach ($items as $item) {
                    $lat = !empty($item['latitude']) ? (float)$item['latitude'] : null;
                    $lng = !empty($item['longitude']) ? (float)$item['longitude'] : null;

                    // Pastikan item memiliki koordinat valid untuk penempatan pin di peta
                    if ($lat === null || $lng === null) {
                        continue;
                    }

                    $typeSlug = $item['type_slug'] ?? $this->getTypeSlug($item['jenis_faskes'] ?? $item['tipe'] ?? '');
                    $rating = (!empty($item['avg_rating']) && (float)$item['avg_rating'] > 0) 
                        ? number_format((float)$item['avg_rating'], 1) 
                        : null;

                    $reviewsCount = !empty($item['total_reviews']) && (int)$item['total_reviews'] > 0
                        ? (int)$item['total_reviews']
                        : 0;

                    $rawName = $item['nama_faskes'] ?? $item['nama'] ?? $item['name'] ?? 'Fasilitas Kesehatan';
                    $jenisFaskes = $item['jenis_faskes'] ?? $item['tipe'] ?? $item['type'] ?? '';
                    $formattedName = $this->formatFaskesName($rawName, $typeSlug, $jenisFaskes);
                    $resolvedType = $this->formatFaskesType($jenisFaskes, $typeSlug, $formattedName);

                    $kota = $item['kota_kabupaten'] ?? $item['kota'] ?? '';
                    $kecamatan = $item['kecamatan'] ?? '';
                    $rawAlamat = $item['alamat_faskes'] ?? $item['alamat'] ?? $item['alamat_lengkap'] ?? $item['jalan'] ?? $item['lokasi'] ?? '';
                    $cleanAddress = $this->formatAddress($rawAlamat, $kota, $kecamatan);

                    $hasSpecificLink = !empty($item['link_maps']) 
                        && !in_array($item['link_maps'], ['-', 'TRUE', 'FALSE']) 
                        && str_starts_with($item['link_maps'], 'http')
                        && (str_contains($item['link_maps'], '@') || str_contains($item['link_maps'], 'place') || str_contains($item['link_maps'], 'maps.app.goo.gl') || str_contains($item['link_maps'], 'dir'));

                    $navUrl = $hasSpecificLink
                        ? $item['link_maps']
                        : $this->getGoogleMapsUrl($formattedName, $lat, $lng, $kota, $rawAlamat);

                    $photo = \App\Support\FaskesVisual::normalizeStorageUrl($item['thumbnail_url'] ?? null);

                    $faskesList[] = [
                        'id' => $item['id_faskes'] ?? $item['id'],
                        'name' => $formattedName,
                        'type' => $resolvedType,
                        'jenis_faskes' => $jenisFaskes,
                        'type_slug' => $typeSlug,
                        'address' => $cleanAddress,
                        'rating' => $rating,
                        'total_reviews' => $reviewsCount,
                        'hospital_class' => $item['kelas'] ?? $item['hospital_class'] ?? null,
                        'thumbnail_url' => $photo,
                        'bpjs_support' => (bool) ($item['_support_bpjs'] ?? $item['is_support_bpjs'] ?? $item['bpjs_support'] ?? false),
                        'distance' => isset($item['distance_km']) ? number_format((float)$item['distance_km'], 1) . ' km' : null,
                        'phone' => !empty($item['kontak']) ? $item['kontak'] : (!empty($item['telp_faskes']) ? $item['telp_faskes'] : null),
                        'lat' => $lat,
                        'lng' => $lng,
                        'foto' => $photo,
                        'navigasi_url' => $navUrl,
                        'detail_url' => url('/faskes/' . ($item['id_faskes'] ?? $item['id'])),
                    ];
                }
            } else {
                $errorMessage = 'Gagal memuat data faskes dari server (Status ' . $response->status() . '). Silakan coba beberapa saat lagi.';
                Log::warning('MapController: Backend API returned status ' . $response->status());
            }
        } catch (\Exception $e) {
            $errorMessage = 'Tidak dapat terhubung ke server faskes. Silakan periksa koneksi atau coba beberapa saat lagi.';
            Log::warning('Failed to fetch map faskes from backend API: ' . $e->getMessage());
        }

        return view('users.map', compact('faskesList', 'errorMessage'));
    }
}

