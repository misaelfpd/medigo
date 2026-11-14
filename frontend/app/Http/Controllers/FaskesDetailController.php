<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FaskesDetailController extends Controller
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

    private function formatAddress($alamat, $kota = '', $kecamatan = '', $provinsi = '')
    {
        $alamat = trim($alamat ?? '');
        $kota = trim($kota ?? '');
        $kecamatan = trim($kecamatan ?? '');
        $provinsi = trim($provinsi ?? '');

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

        $parts = array_filter([$alamat, $kota, $provinsi], fn ($p) => !empty($p));

        $full = '';
        foreach ($parts as $p) {
            if ($full === '') {
                $full = $p;
            } elseif (!str_contains(strtolower($full), strtolower($p))) {
                $full .= ', ' . $p;
            }
        }

        return $full;
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

    private function formatFacilities(array $rawFasilitas): array
    {
        return array_map(function ($item) {
            return [
                'id' => is_array($item) ? ($item['id_fasilitas'] ?? null) : null,
                'title' => trim(is_array($item) ? ($item['nama_fasilitas'] ?? '') : (string) $item),
            ];
        }, $rawFasilitas);
    }

    private function formatSpecialties(array $rawSpesialisasi): array
    {
        return array_map(function ($item) {
            return [
                'id' => is_array($item) ? ($item['id_spesialisasi'] ?? null) : null,
                'title' => trim(is_array($item) ? ($item['nama_spesialisasi'] ?? '') : (string) $item),
            ];
        }, $rawSpesialisasi);
    }

    private function getBackendToken()
    {
        $token = session('user_backend_token');
        if ($token) {
            return $token;
        }

        if (!Auth::check()) {
            return null;
        }

        try {
            $rawBackendUrl = config('services.medigo.url', env('MEDIGO_BE', 'http://127.0.0.1:8005'));
            $backendUrl = rtrim(str_replace('localhost', '127.0.0.1', $rawBackendUrl), '/');

            $response = Http::timeout(5)
                ->withHeaders(['X-Service-Key' => (string) config('services.medigo.service_key')])
                ->post("{$backendUrl}/api/auth/token-by-email", [
                    'email' => Auth::user()->email,
                ]);

            if ($response->successful()) {
                $token = $response->json('access_token');
                $userData = $response->json('user');
                if ($token) {
                    session([
                        'user_backend_token' => $token,
                        'user_backend_data'  => $userData,
                    ]);
                    return $token;
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed obtaining backend token for faskes detail: ' . $e->getMessage());
        }

        return null;
    }

    public function show(Request $request, $id)
    {
        $rawBackendUrl = config('services.medigo.url', env('MEDIGO_BE', 'http://127.0.0.1:8005'));
        $backendUrl = rtrim(str_replace('localhost', '127.0.0.1', $rawBackendUrl), '/');
        $faskes = null;
        $reviews = [];
        $myComment = null;
        $hasMoreComments = false;
        $totalComments = 0;

        $user = Auth::user();
        $token = $this->getBackendToken();
        $backendUser = session('user_backend_data');
        $backendUserId = $backendUser['id_user'] ?? $backendUser['id'] ?? null;

        try {
            $faskesQueryParams = [];
            if ($request->filled('lat')) {
                $faskesQueryParams['lat'] = $request->query('lat');
            } elseif ($request->filled('latitude')) {
                $faskesQueryParams['lat'] = $request->query('latitude');
            }
            if ($request->filled('lng')) {
                $faskesQueryParams['lng'] = $request->query('lng');
            } elseif ($request->filled('longitude')) {
                $faskesQueryParams['lng'] = $request->query('longitude');
            }

            $response = Http::timeout(8)->get("{$backendUrl}/api/faskes/{$id}", $faskesQueryParams);
            if ($response->successful()) {
                $body = $response->json();
                $item = $body['data']['faskes'] ?? ($body['data'] ?? null);

                if ($item) {
                    $jenisFaskes = $item['jenis_faskes'] ?? $item['tipe'] ?? '';
                    $typeSlug = $item['type_slug'] ?? $this->getTypeSlug($jenisFaskes);

                    $rawRating = $item['avg_rating'] ?? null;
                    $totalReviewsCount = (int)($item['total_reviews'] ?? 0);
                    $avgRating = ($totalReviewsCount > 0 && $rawRating !== null && (float)$rawRating > 0)
                        ? number_format((float)$rawRating, 1)
                        : null;

                    // Murni koordinat dari API, jika kosong jadikan null
                    $lat = !empty($item['latitude']) ? (float)$item['latitude'] : null;
                    $lng = !empty($item['longitude']) ? (float)$item['longitude'] : null;

                    $rawName = $item['nama_faskes'] ?? 'Fasilitas Kesehatan';
                    $formattedName = $this->formatFaskesName($rawName, $typeSlug, $jenisFaskes);

                    $hasSpecificLink = !empty($item['link_maps'])
                        && !in_array($item['link_maps'], ['-', 'TRUE', 'FALSE'])
                        && str_starts_with($item['link_maps'], 'http')
                        && (str_contains($item['link_maps'], '@') || str_contains($item['link_maps'], 'place') || str_contains($item['link_maps'], 'maps.app.goo.gl') || str_contains($item['link_maps'], 'dir'));

                    $kota = $item['kota_kabupaten'] ?? $item['kota'] ?? '';
                    $kecamatan = $item['kecamatan'] ?? '';
                    $provinsi = $item['provinsi'] ?? '';
                    $alamatFaskes = $item['alamat_faskes'] ?? $item['alamat'] ?? $item['alamat_lengkap'] ?? $item['jalan'] ?? '';

                    $fullAddress = $this->formatAddress($alamatFaskes, $kota, $kecamatan, $provinsi);
                    $resolvedType = $this->formatFaskesType($jenisFaskes, $typeSlug, $formattedName);

                    $navUrl = $hasSpecificLink
                        ? $item['link_maps']
                        : $this->getGoogleMapsUrl($formattedName, $lat, $lng, $kota, $alamatFaskes);

                    $phone = !empty($item['kontak']) ? $item['kontak'] : (!empty($item['telp_faskes']) ? $item['telp_faskes'] : (!empty($item['telepon']) ? $item['telepon'] : '-'));
                    
                    // Ambil distance dari backend jika ada koordinat, atau dari URL query parameter
                    $distance = $body['data']['distance']
                        ?? $item['distance']
                        ?? $body['data']['jarak_teks']
                        ?? $item['jarak_teks']
                        ?? $request->query('distance')
                        ?? null;

                    $distanceKm = $body['data']['distance_km']
                        ?? $item['distance_km']
                        ?? $body['data']['jarak_km']
                        ?? $item['jarak_km']
                        ?? null;

                    if ($distanceKm !== null) {
                        $distanceKm = (float) $distanceKm;
                        if (!$distance) {
                            $distance = number_format($distanceKm, 1, '.', '') . ' km';
                        }
                    }

                    $rawFasilitas = $body['data']['fasilitas'] ?? $item['fasilitas'] ?? [];
                    $rawSpesialisasi = $body['data']['spesialisasi'] ?? $item['spesialisasi'] ?? [];
                    
                    $deskripsiFallback = "{$formattedName} adalah fasilitas pelayanan kesehatan {$resolvedType} yang berlokasi di " . ($kota ?: 'kota Anda') . ".";

                    $faskes = [
                        'id' => $item['id_faskes'] ?? $item['id'],
                        'nama_faskes' => $formattedName,
                        'tipe' => $resolvedType,
                        'type_slug' => $typeSlug,
                        'kelas' => !empty($item['kelas']) ? $item['kelas'] : null,
                        'is_support_bpjs' => (bool) ($item['_support_bpjs'] ?? $item['is_support_bpjs'] ?? true),
                        'alamat' => $fullAddress,
                        'kota_kabupaten' => $kota,
                        'phone' => $phone,
                        'avg_rating' => $avgRating,
                        'avg_rating_display' => $avgRating ? $avgRating : 'Belum dirating',
                        'total_reviews' => $totalReviewsCount,
                        'distance' => $distance,
                        'distance_km' => $distanceKm,
                        'lat' => $lat,
                        'lng' => $lng,
                        'navigasi_url' => $navUrl,
                        'description' => $item['deskripsi'] ?? $item['description'] ?? $deskripsiFallback,
                        'fasilitas' => $this->formatFacilities($rawFasilitas),
                        'spesialisasi' => $this->formatSpecialties($rawSpesialisasi),
                        // Foto dari backend (fitur CRUD foto faskes): banner atas, thumbnail kartu, galeri
                        'banner_url' => $item['banner_url'] ?? ($body['data']['foto']['banner']['url'] ?? null),
                        'thumbnail_url' => $item['thumbnail_url'] ?? ($body['data']['foto']['thumbnail']['url'] ?? null),
                        'galeri' => collect($body['data']['foto']['galeri'] ?? $item['foto']['galeri'] ?? [])
                            ->map(fn ($foto) => [
                                'url' => $foto['url'] ?? null,
                                'keterangan' => $foto['keterangan'] ?? null,
                            ])
                            ->filter(fn ($foto) => ! empty($foto['url']))
                            ->values()
                            ->all(),
                    ];
                }
            }

            // Ambil Komentar Jika Faskes Ada
            if ($faskes) {
                $commentsReq = Http::timeout(8);
                if ($token) {
                    $commentsReq = $commentsReq->withToken($token);
                }
                $commParams = ['limit' => 4, 'page' => 1];
                if ($user) {
                    $commParams['current_user_email'] = $user->email;
                    $commParams['current_user_id'] = $backendUserId ?: $user->id;
                }

                $commentsResp = $commentsReq->get("{$backendUrl}/api/faskes/{$id}/comments", $commParams);
                if ($commentsResp->successful()) {
                    $commData = $commentsResp->json('data') ?? [];
                    $reviews = $commData['comments'] ?? [];
                    $myComment = $commData['my_comment'] ?? null;
                    $hasMoreComments = (bool)($commData['has_more'] ?? false);

                    if (isset($commData['avg_rating'])) {
                        $faskes['avg_rating'] = $commData['avg_rating'];
                        $faskes['avg_rating_display'] = $commData['avg_rating'] ? $commData['avg_rating'] : 'Belum dirating';
                    }
                    if (isset($commData['total_reviews'])) {
                        $faskes['total_reviews'] = (int)$commData['total_reviews'];
                    }
                    $ratingBreakdown = $commData['rating_breakdown'] ?? [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
                    $ratingPercentages = $commData['rating_percentages'] ?? [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to fetch faskes detail or comments from backend API: ' . $e->getMessage());
        }

        // Jika API mati atau ID Faskes tidak ada, langsung abort 404 (Tidak menggunakan dummy Pondok Indah lagi)
        if (!$faskes) {
            abort(404, 'Fasilitas Kesehatan tidak ditemukan atau terjadi gangguan koneksi ke server.');
        }

        $ratingBreakdown = $ratingBreakdown ?? [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        $ratingPercentages = $ratingPercentages ?? [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        $isLoggedIn = Auth::check();
        $currentUserId = $backendUserId ?: ($user ? $user->id : null);

        return view('users.detail', compact('faskes', 'reviews', 'myComment', 'hasMoreComments', 'totalComments', 'ratingBreakdown', 'ratingPercentages', 'isLoggedIn', 'currentUserId'));
    }

    public function getComments(Request $request, $id)
    {
        $rawBackendUrl = config('services.medigo.url', env('MEDIGO_BE', 'http://127.0.0.1:8005'));
        $backendUrl = rtrim(str_replace('localhost', '127.0.0.1', $rawBackendUrl), '/');

        $token = $this->getBackendToken();
        $user = Auth::user();
        $backendUser = session('user_backend_data');
        $backendUserId = $backendUser['id_user'] ?? $backendUser['id'] ?? null;

        $http = Http::timeout(8);
        if ($token) {
            $http = $http->withToken($token);
        }

        $params = [
            'page' => (int)$request->input('page', 1),
            'limit' => (int)$request->input('limit', 4),
        ];

        if ($user) {
            $params['current_user_email'] = $user->email;
            $params['current_user_id'] = $backendUserId ?: $user->id;
        }

        try {
            $response = $http->get("{$backendUrl}/api/faskes/{$id}/comments", $params);
            if ($response->successful()) {
                return response()->json($response->json());
            }
            return response()->json(['status' => 'error', 'message' => 'Gagal memuat ulasan.'], $response->status());
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function storeComment(Request $request, $id)
    {
        if (!Auth::check()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Silakan masuk terlebih dahulu untuk memberikan ulasan.'
            ], 401);
        }

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'body' => 'nullable|string',
        ]);

        $rawBackendUrl = config('services.medigo.url', env('MEDIGO_BE', 'http://127.0.0.1:8005'));
        $backendUrl = rtrim(str_replace('localhost', '127.0.0.1', $rawBackendUrl), '/');
        $token = session('user_backend_token');

        try {
            $http = Http::timeout(8);
            if ($token) {
                $http = $http->withToken($token);
            }

            $response = $http->post("{$backendUrl}/api/faskes/{$id}/comments", [
                'rating' => (int)$request->rating,
                'body' => $request->body,
            ]);

            if ($response->successful()) {
                return response()->json($response->json(), 201);
            }

            return response()->json([
                'status' => 'error',
                'message' => $response->json('message') ?? 'Gagal menyimpan ulasan.'
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function updateComment(Request $request, $id, $commentId)
    {
        if (!Auth::check()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Silakan masuk terlebih dahulu untuk mengubah ulasan.'
            ], 401);
        }

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'body' => 'nullable|string',
        ]);

        $rawBackendUrl = config('services.medigo.url', env('MEDIGO_BE', 'http://127.0.0.1:8005'));
        $backendUrl = rtrim(str_replace('localhost', '127.0.0.1', $rawBackendUrl), '/');
        $token = session('user_backend_token');

        try {
            $http = Http::timeout(8);
            if ($token) {
                $http = $http->withToken($token);
            }

            $response = $http->put("{$backendUrl}/api/comments/{$commentId}", [
                'rating' => (int)$request->rating,
                'body' => $request->body,
            ]);

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json([
                'status' => 'error',
                'message' => $response->json('message') ?? 'Gagal memperbarui ulasan.'
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function destroyComment(Request $request, $id, $commentId)
    {
        if (!Auth::check()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Silakan masuk terlebih dahulu untuk menghapus ulasan.'
            ], 401);
        }

        $rawBackendUrl = config('services.medigo.url', env('MEDIGO_BE', 'http://127.0.0.1:8005'));
        $backendUrl = rtrim(str_replace('localhost', '127.0.0.1', $rawBackendUrl), '/');
        $token = session('user_backend_token');

        try {
            $http = Http::timeout(8);
            if ($token) {
                $http = $http->withToken($token);
            }

            $response = $http->delete("{$backendUrl}/api/comments/{$commentId}");

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json([
                'status' => 'error',
                'message' => $response->json('message') ?? 'Gagal menghapus ulasan.'
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function destroyReview(Request $request, $commentId)
    {
        return $this->destroyComment($request, null, $commentId);
    }

    public function storeFeedback(Request $request, $id)
    {
        $request->validate([
            'category' => 'required|string|max:100',
            'notes' => 'required|string|max:2000',
            'suggested_correction' => 'nullable|string|max:1000',
        ]);

        $rawBackendUrl = config('services.medigo.url', env('MEDIGO_BE', 'http://127.0.0.1:8005'));
        $backendUrl = rtrim(str_replace('localhost', '127.0.0.1', $rawBackendUrl), '/');
        $token = $this->getBackendToken();
        $user = Auth::user();

        try {
            $http = Http::timeout(8);
            if ($token) {
                $http = $http->withToken($token);
            }

            $response = $http->post("{$backendUrl}/api/faskes/{$id}/feedback", [
                'category' => $request->category,
                'notes' => $request->notes,
                'suggested_correction' => $request->suggested_correction,
                'user_name' => $user ? $user->name : $request->input('user_name'),
                'user_email' => $user ? $user->email : $request->input('user_email'),
            ]);

            if ($response->successful()) {
                return response()->json($response->json(), 201);
            }

            return response()->json([
                'status' => 'error',
                'message' => $response->json('message') ?? 'Gagal mengirim masukan data.'
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function reportComment(Request $request, $id, $commentId)
    {
        if (!Auth::check()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Silakan masuk terlebih dahulu untuk melaporkan ulasan.'
            ], 401);
        }

        $request->validate([
            'reason' => 'required|string|max:150',
            'notes' => 'nullable|string|max:1000',
        ]);

        $rawBackendUrl = config('services.medigo.url', env('MEDIGO_BE', 'http://127.0.0.1:8005'));
        $backendUrl = rtrim(str_replace('localhost', '127.0.0.1', $rawBackendUrl), '/');
        $token = $this->getBackendToken();

        try {
            $http = Http::timeout(8);
            if ($token) {
                $http = $http->withToken($token);
            }

            $response = $http->post("{$backendUrl}/api/faskes-comments/{$commentId}/report", [
                'reason' => $request->reason,
                'notes' => $request->notes,
            ]);

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json([
                'status' => 'error',
                'message' => $response->json('message') ?? 'Gagal mengirim laporan ulasan.'
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}