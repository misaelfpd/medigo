<?php

namespace App\Http\Controllers;

use App\Models\Faskes;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FaskesController extends Controller
{
    /**
     * Map faskes type to a frontend-friendly slug.
     */
    private function getTypeSlug($jenis_faskes)
    {
        $tipeLower = strtolower($jenis_faskes ?? '');
        if (str_contains($tipeLower, 'rumah sakit') || str_contains($tipeLower, 'rs')) {
            return 'hospital';
        } elseif (str_contains($tipeLower, 'puskesmas')) {
            return 'puskesmas';
        } elseif (str_contains($tipeLower, 'klinik') || str_contains($tipeLower, 'dokter') || str_contains($tipeLower, 'apotek')) {
            return 'clinic';
        }

        return 'other';
    }

    private function formatFaskesName($name, $typeSlug, $jenis_faskes = '')
    {
        $name = trim($name ?? '');
        $isPuskesmas = ($typeSlug === 'puskesmas') || str_contains(strtolower($jenis_faskes ?? ''), 'puskesmas') || str_contains(strtolower($name), 'puskesmas');
        if ($isPuskesmas && ! preg_match('/^(puskesmas|pkm)\b/i', $name)) {
            return 'Puskesmas '.$name;
        }

        return $name;
    }

    private function getGoogleMapsUrl($name, $lat = null, $lng = null, $kota = '', $alamat_faskes = '')
    {
        $parts = array_filter([trim($name), trim($alamat_faskes), trim($kota)]);
        $queryName = implode(', ', $parts);
        $encodedName = urlencode($queryName);

        if ($lat && $lng) {
            return "https://www.google.com/maps/search/{$encodedName}/@{$lat},{$lng},17z";
        }

        return "https://www.google.com/maps/search/?api=1&query={$encodedName}";
    }

    // 1. Ambil data Faskes (dilengkapi Pencarian, GPS, Rating & Filter)
    public function index(Request $request)
    {
        $query = DB::table('faskes');

        // Pencarian (Nama / Alamat / Kota / Provinsi)
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('faskes.nama_faskes', 'like', "%{$search}%")
                    ->orWhere('faskes.alamat_faskes', 'like', "%{$search}%")
                    ->orWhere('faskes.kota_kabupaten', 'like', "%{$search}%")
                    ->orWhere('faskes.provinsi', 'like', "%{$search}%");
            });
        }

        // Filter BPJS (1 / 0 / true / false)
        if ($request->has('_support_bpjs') && $request->_support_bpjs !== null && $request->_support_bpjs !== '') {
            $bpjsVal = filter_var($request->_support_bpjs, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            $query->where('faskes._support_bpjs', $bpjsVal);
        }

        // Filter Jenis / Tipe Faskes
        if ($request->filled('jenis_faskes') || $request->filled('tipe') || $request->filled('type')) {
            $typeInput = $request->input('jenis_faskes') ?? $request->input('tipe') ?? $request->input('type');
            if ($typeInput === 'hospital') {
                $query->where(function ($q) {
                    $q->where('faskes.jenis_faskes', 'like', '%Rumah Sakit%')
                        ->orWhere('faskes.jenis_faskes', 'like', '%RS%');
                });
            } elseif ($typeInput === 'clinic') {
                $query->where(function ($q) {
                    $q->where('faskes.jenis_faskes', 'like', '%Klinik%')
                        ->orWhere('faskes.jenis_faskes', 'like', '%Dokter%')
                        ->orWhere('faskes.jenis_faskes', 'like', '%Apotek%');
                });
            } elseif ($typeInput === 'puskesmas') {
                $query->where('faskes.jenis_faskes', 'like', '%Puskesmas%');
            } else {
                $query->where('faskes.jenis_faskes', 'like', "%{$typeInput}%");
            }
        }

        // Filter Provinsi
        if ($request->filled('provinsi')) {
            $query->where('faskes.provinsi', 'like', "%{$request->provinsi}%");
        }

        // Filter Kota / Kabupaten
        if ($request->filled('kota') || $request->filled('kota_kabupaten')) {
            $kota = $request->input('kota') ?? $request->input('kota_kabupaten');
            $query->where('faskes.kota_kabupaten', 'like', "%{$kota}%");
        }

        // Filter Lokasi Umum (Kota / Kabupaten / Provinsi / Alamat)
        if ($request->filled('lokasi')) {
            $lokasi = trim($request->lokasi);
            $query->where(function ($q) use ($lokasi) {
                $q->where('faskes.kota_kabupaten', 'like', "%{$lokasi}%")
                    ->orWhere('faskes.provinsi', 'like', "%{$lokasi}%")
                    ->orWhere('faskes.alamat_faskes', 'like', "%{$lokasi}%");
            });
        }

        // Filter Hanya yang memiliki koordinat GPS
        if ($request->boolean('with_coords') || $request->input('with_coords') == '1') {
            $query->whereNotNull('faskes.latitude')
                ->whereNotNull('faskes.longitude');
        }

        // Filter & Urutkan Berdasarkan Jarak GPS jika lat & lng dikirimkan
        if ($request->filled('lat') && $request->filled('lng')) {
            $lat = (float) $request->lat;
            $lng = (float) $request->lng;

            // Haversine formula (km)
            $haversine = "(6371 * acos(cos(radians({$lat})) * cos(radians(faskes.latitude)) * cos(radians(faskes.longitude) - radians({$lng})) + sin(radians({$lat})) * sin(radians(faskes.latitude))))";

            $query->select('faskes.*')
                ->selectRaw("{$haversine} AS distance_km")
                ->whereNotNull('faskes.latitude')
                ->whereNotNull('faskes.longitude');

            // Filter radius jika diberikan
            if ($request->filled('radius')) {
                $radius = (float) $request->radius;
                $query->having('distance_km', '<=', $radius);
            }

            $query->orderBy('distance_km', 'asc');
        } else {
            $query->select('faskes.*')->orderBy('faskes.id_faskes', 'asc');
        }

        $limitCount = min((int) $request->input('limit', $request->input('per_page', 30)), 300);

        // All non-paginated
        if ($request->boolean('all') || $request->input('paginate') === 'false') {
            $faskes = $query->limit($limitCount)->get();

            $faskesIds = $faskes->pluck('id_faskes')->toArray();
            $comments = DB::table('komentar')
                ->whereIn('id_faskes', $faskesIds)
                ->select('id_faskes', DB::raw('ROUND(AVG(rating), 1) as avg_rating'), DB::raw('COUNT(id_komentar) as total_reviews'))
                ->groupBy('id_faskes')
                ->get()
                ->keyBy('id_faskes');

            $faskes->transform(function ($item) use ($comments) {
                $item->type_slug = $this->getTypeSlug($item->jenis_faskes);
                $item->nama_faskes = $this->formatFaskesName($item->nama_faskes, $item->type_slug, $item->jenis_faskes);
                $hasComment = isset($comments[$item->id_faskes]) && $comments[$item->id_faskes]->total_reviews > 0;
                $item->avg_rating = $hasComment ? (string) $comments[$item->id_faskes]->avg_rating : null;
                $item->total_reviews = $hasComment ? (int) $comments[$item->id_faskes]->total_reviews : 0;
                $hasSpecific = ! empty($item->link_maps) && ! in_array($item->link_maps, ['-', 'TRUE', 'FALSE']) && str_starts_with($item->link_maps, 'http') && (str_contains($item->link_maps, '@') || str_contains($item->link_maps, 'place') || str_contains($item->link_maps, 'maps.app.goo.gl') || str_contains($item->link_maps, 'dir'));
                if (! $hasSpecific) {
                    $item->link_maps = $this->getGoogleMapsUrl($item->nama_faskes, $item->latitude ?? null, $item->longitude ?? null, $item->kota_kabupaten ?? '', $item->alamat_faskes ?? '');
                }

                return $item;
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil mengambil data faskes',
                'total' => $faskes->count(),
                'data' => $faskes,
            ]);
        }

        $paginated = $query->paginate($limitCount);

        $faskesIds = $paginated->getCollection()->pluck('id_faskes')->toArray();
        $comments = DB::table('komentar')
            ->whereIn('id_faskes', $faskesIds)
            ->select('id_faskes', DB::raw('ROUND(AVG(rating), 1) as avg_rating'), DB::raw('COUNT(id_komentar) as total_reviews'))
            ->groupBy('id_faskes')
            ->get()
            ->keyBy('id_faskes');

        $paginated->getCollection()->transform(function ($item) use ($comments) {
            $item->type_slug = $this->getTypeSlug($item->jenis_faskes);
            $item->nama_faskes = $this->formatFaskesName($item->nama_faskes, $item->type_slug, $item->jenis_faskes);
            $hasComment = isset($comments[$item->id_faskes]) && $comments[$item->id_faskes]->total_reviews > 0;
            $item->avg_rating = $hasComment ? (string) $comments[$item->id_faskes]->avg_rating : null;
            $item->total_reviews = $hasComment ? (int) $comments[$item->id_faskes]->total_reviews : 0;
            $hasSpecific = ! empty($item->link_maps) && ! in_array($item->link_maps, ['-', 'TRUE', 'FALSE']) && str_starts_with($item->link_maps, 'http') && (str_contains($item->link_maps, '@') || str_contains($item->link_maps, 'place') || str_contains($item->link_maps, 'maps.app.goo.gl') || str_contains($item->link_maps, 'dir'));
            if (! $hasSpecific) {
                $item->link_maps = $this->getGoogleMapsUrl($item->nama_faskes, $item->latitude ?? null, $item->longitude ?? null, $item->kota_kabupaten ?? '', $item->alamat_faskes ?? '');
            }

            return $item;
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil mengambil data faskes',
            'data' => $paginated,
        ]);
    }

    // 2. Ambil detail spesifik satu Faskes (buat halaman detail)
    public function show($id)
    {
        // 1. Ambil data utama faskesnya
        $faskes = DB::table('faskes')
            ->where('id_faskes', $id)
            ->first();

        if (! $faskes) {
            return response()->json(['message' => 'Fasilitas kesehatan tidak ditemukan'], 404);
        }

        // Hitung agregasi ulasan dari tabel komentar
        $ratingStats = DB::table('komentar')
            ->where('id_faskes', $id)
            ->select(
                DB::raw('ROUND(AVG(rating), 1) as avg_rating'),
                DB::raw('COUNT(id_komentar) as total_reviews')
            )
            ->first();

        $totalReviews = (int) ($ratingStats->total_reviews ?? 0);
        $avgRating = $totalReviews > 0 ? (string) $ratingStats->avg_rating : null;

        $faskes->total_reviews = $totalReviews;
        $faskes->avg_rating = $avgRating;

        $faskes->type_slug = $this->getTypeSlug($faskes->jenis_faskes);
        $faskes->nama_faskes = $this->formatFaskesName($faskes->nama_faskes, $faskes->type_slug, $faskes->jenis_faskes);

        $hasSpecific = ! empty($faskes->link_maps) && ! in_array($faskes->link_maps, ['-', 'TRUE', 'FALSE']) && str_starts_with($faskes->link_maps, 'http') && (str_contains($faskes->link_maps, '@') || str_contains($faskes->link_maps, 'place') || str_contains($faskes->link_maps, 'maps.app.goo.gl') || str_contains($faskes->link_maps, 'dir'));
        if (! $hasSpecific) {
            $faskes->link_maps = $this->getGoogleMapsUrl($faskes->nama_faskes, $faskes->latitude ?? null, $faskes->longitude ?? null, $faskes->kota_kabupaten ?? '', $faskes->alamat_faskes ?? '');
        }

        // 2. Ambil semua ulasan yang terhubung ke faskes ini dari tabel komentar
        $reviews = DB::table('komentar')
            ->join('user', 'komentar.id_user', '=', 'user.id_user')
            ->where('komentar.id_faskes', $id)
            ->select(
                'komentar.*',
                'user.nama_user as reviewer_name',
                'user.email_user as reviewer_email'
            )
            ->orderBy('komentar.created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil mengambil detail faskes',
            'data' => [
                'faskes' => $faskes,
                'reviews' => $reviews,
            ],
        ]);
    }

    // 3. Tambah Faskes Baru (Admin)
    public function store(Request $request)
    {
        $request->validate([
            'nama_faskes' => 'required|string|max:255',
            'jenis_faskes' => 'required|string|max:100',
            'alamat_faskes' => 'required|string',
            'kota_kabupaten' => 'nullable|string|max:100',
            'provinsi' => 'nullable|string|max:100',
            'kelas' => 'nullable|string|max:10',
            '_support_bpjs' => 'nullable',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'link_maps' => 'nullable|string',
        ]);

        $bpjsInput = $request->input('_support_bpjs');
        $isBpjs = null;
        if ($bpjsInput !== null && $bpjsInput !== '' && $bpjsInput !== 'unknown') {
            $isBpjs = filter_var($bpjsInput, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        }

        $kelasInput = $request->input('kelas');
        if ($kelasInput === '' || $kelasInput === 'unknown' || $kelasInput === 'Tidak Diketahui') {
            $kelasInput = null;
        }

        $namaFaskes = trim($request->nama_faskes);
        $kotaKab = $request->kota_kabupaten ?? 'Jakarta Selatan';
        $lat = $request->filled('latitude') ? (float) $request->latitude : null;
        $lng = $request->filled('longitude') ? (float) $request->longitude : null;
        $linkMaps = trim($request->input('link_maps', '') ?? '');

        // Smart link & coordinate extraction
        if (! empty($linkMaps) && $linkMaps !== '-' && $linkMaps !== 'TRUE' && $linkMaps !== 'FALSE') {
            if ($lat === null || $lng === null) {
                if (preg_match('/[?&](?:q|destination)=([-+]?\d{1,3}(?:\.\d+)?),([-+]?\d{1,3}(?:\.\d+)?)/i', $linkMaps, $m)) {
                    $lat = (float) $m[1];
                    $lng = (float) $m[2];
                } elseif (preg_match('/@([-+]?\d{1,3}(?:\.\d+)?),([-+]?\d{1,3}(?:\.\d+)?)/i', $linkMaps, $m)) {
                    $lat = (float) $m[1];
                    $lng = (float) $m[2];
                }
            }
        } else {
            if ($lat !== null && $lng !== null) {
                $linkMaps = "https://www.google.com/maps/search/?api=1&query={$lat},{$lng}";
            } else {
                $linkMaps = 'https://www.google.com/maps/search/?api=1&query='.urlencode($namaFaskes.' '.$kotaKab);
            }
        }

        $id = DB::table('faskes')->insertGetId([
            'nama_faskes' => $namaFaskes,
            'jenis_faskes' => $request->jenis_faskes,
            'kelas' => $kelasInput,
            'provinsi' => $request->provinsi ?? 'DKI Jakarta',
            'kota_kabupaten' => $kotaKab,
            'kecamatan' => $request->kecamatan ?? null,
            'alamat_faskes' => $request->alamat_faskes,
            'link_maps' => $linkMaps,
            'latitude' => $lat,
            'longitude' => $lng,
            '_support_bpjs' => $isBpjs,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Fasilitas kesehatan berhasil ditambahkan',
            'data' => ['id' => $id],
        ], 201);
    }

    // 4. Update Data Faskes (Admin)
    public function update(Request $request, $id)
    {
        $faskes = DB::table('faskes')->where('id_faskes', $id)->first();
        if (! $faskes) {
            return response()->json(['message' => 'Fasilitas kesehatan tidak ditemukan'], 404);
        }

        $request->validate([
            'nama_faskes' => 'sometimes|required|string|max:255',
            'jenis_faskes' => 'sometimes|required|string|max:100',
            'alamat_faskes' => 'sometimes|required|string',
            'kota_kabupaten' => 'nullable|string|max:100',
            'provinsi' => 'nullable|string|max:100',
            'kelas' => 'nullable|string|max:50',
            '_support_bpjs' => 'nullable',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'link_maps' => 'nullable|string',
        ]);

        $updateData = [];

        if ($request->has('nama_faskes')) {
            $updateData['nama_faskes'] = $request->nama_faskes;
        }
        if ($request->has('jenis_faskes')) {
            $updateData['jenis_faskes'] = $request->jenis_faskes;
        }
        if ($request->has('kelas')) {
            $kelasVal = $request->kelas;
            if ($kelasVal === '' || $kelasVal === 'unknown' || $kelasVal === 'Tidak Diketahui') {
                $updateData['kelas'] = null;
            } else {
                $updateData['kelas'] = $kelasVal;
            }
        }
        if ($request->has('provinsi')) {
            $updateData['provinsi'] = $request->provinsi;
        }
        if ($request->has('kota_kabupaten')) {
            $updateData['kota_kabupaten'] = $request->kota_kabupaten;
        }
        if ($request->has('alamat_faskes')) {
            $updateData['alamat_faskes'] = $request->alamat_faskes;
        }

        $targetLat = $request->has('latitude') && $request->filled('latitude') ? (float) $request->latitude : ($faskes->latitude !== null ? (float) $faskes->latitude : null);
        $targetLng = $request->has('longitude') && $request->filled('longitude') ? (float) $request->longitude : ($faskes->longitude !== null ? (float) $faskes->longitude : null);

        if ($request->has('link_maps')) {
            $linkMaps = trim($request->input('link_maps', '') ?? '');
            if (! empty($linkMaps) && $linkMaps !== '-' && $linkMaps !== 'TRUE' && $linkMaps !== 'FALSE') {
                if (! $request->filled('latitude') || ! $request->filled('longitude')) {
                    if (preg_match('/[?&](?:q|destination)=([-+]?\d{1,3}(?:\.\d+)?),([-+]?\d{1,3}(?:\.\d+)?)/i', $linkMaps, $m)) {
                        $targetLat = (float) $m[1];
                        $targetLng = (float) $m[2];
                        $updateData['latitude'] = $targetLat;
                        $updateData['longitude'] = $targetLng;
                    } elseif (preg_match('/@([-+]?\d{1,3}(?:\.\d+)?),([-+]?\d{1,3}(?:\.\d+)?)/i', $linkMaps, $m)) {
                        $targetLat = (float) $m[1];
                        $targetLng = (float) $m[2];
                        $updateData['latitude'] = $targetLat;
                        $updateData['longitude'] = $targetLng;
                    }
                }
                $updateData['link_maps'] = $linkMaps;
            } else {
                if ($targetLat !== null && $targetLng !== null) {
                    $updateData['link_maps'] = "https://www.google.com/maps/search/?api=1&query={$targetLat},{$targetLng}";
                } else {
                    $namaFaskes = $request->nama_faskes ?? $faskes->nama_faskes;
                    $kota = $request->kota_kabupaten ?? $faskes->kota_kabupaten;
                    $updateData['link_maps'] = 'https://www.google.com/maps/search/?api=1&query='.urlencode($namaFaskes.' '.$kota);
                }
            }
        }

        if ($request->has('latitude')) {
            $updateData['latitude'] = $request->filled('latitude') ? (float) $request->latitude : null;
        }
        if ($request->has('longitude')) {
            $updateData['longitude'] = $request->filled('longitude') ? (float) $request->longitude : null;
        }

        if ($request->has('_support_bpjs')) {
            $bpjsVal = $request->_support_bpjs;
            if ($bpjsVal === '' || $bpjsVal === 'unknown' || $bpjsVal === 'null' || $bpjsVal === null) {
                $updateData['_support_bpjs'] = null;
            } else {
                $updateData['_support_bpjs'] = filter_var($bpjsVal, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            }
        }

        $updateData['updated_at'] = now();

        DB::table('faskes')->where('id_faskes', $id)->update($updateData);

        return response()->json([
            'status' => 'success',
            'message' => 'Fasilitas kesehatan berhasil diperbarui',
        ]);
    }

    // 5. Hapus Faskes (Admin)
    public function destroy($id)
    {
        $faskes = DB::table('faskes')->where('id_faskes', $id)->first();
        if (! $faskes) {
            return response()->json(['message' => 'Fasilitas kesehatan tidak ditemukan'], 404);
        }

        DB::table('faskes')->where('id_faskes', $id)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Fasilitas kesehatan berhasil dihapus',
        ]);
    }

    // 6. Ambil Statistik Dashboard Admin
    public function getAdminStats()
    {
        $totalUsers = DB::table('user')->count();
        $totalFaskes = DB::table('faskes')->count();
        $totalDiscussions = DB::table('diskusi')->count();

        // Ambil 5 aktivitas terbaru dari tabel log_aktivitas
        $recentLogs = DB::table('log_aktivitas')
            ->leftJoin('user', 'log_aktivitas.id_user', '=', 'user.id_user')
            ->select('log_aktivitas.*', 'user.nama_user')
            ->orderBy('log_aktivitas.created_at', 'desc')
            ->limit(5)
            ->get();

        $activities = $recentLogs->map(function ($log) {
            $type = 'general';
            $icon = 'bi-activity';
            $lowerAction = strtolower($log->tipe_aksi ?? '');

            if (str_contains($lowerAction, 'diskusi') || str_contains($lowerAction, 'forum')) {
                $type = 'forum';
                $icon = 'bi-chat-left-text';
            } elseif (str_contains($lowerAction, 'ulasan') || str_contains($lowerAction, 'faskes') || str_contains($lowerAction, 'komentar')) {
                $type = 'faskes';
                $icon = 'bi-hospital';
            } elseif (str_contains($lowerAction, 'user') || str_contains($lowerAction, 'daftar') || str_contains($lowerAction, 'login')) {
                $type = 'user';
                $icon = 'bi-person';
            }

            return [
                'id' => $log->id_log,
                'type' => $type,
                'icon' => $icon,
                'title' => $log->deskripsi,
                'user' => $log->nama_user ?? 'Sistem',
                'time' => $log->created_at ? Carbon::parse($log->created_at)->diffForHumans() : '-',
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_users' => $totalUsers,
                'total_faskes' => $totalFaskes,
                'total_discussions' => $totalDiscussions,
                'activities' => $activities,
            ],
        ]);
    }
    /**
     * Method Utama untuk Filtering & AI Function Calling
     * 
     * @param array $params Parameter pencarian (bisa dari $request->all() atau dari AI Function Call)
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchFaskes(Request $request)
    {
        // Mendukung input dari Request HTTP biasa maupun array langsung dari AI Function Calling
        $params = $request->all();

        $query = DB::table('faskes');

        // 1. Filter Berdasarkan Daerah Saya (Koordinat GPS & Radius)
        $lat = $params['lat'] ?? null;
        $lng = $params['lng'] ?? null;
        $radius = $params['radius'] ?? 5; // Default radius 5 km jika tidak diisi

        if ($lat && $lng) {
            $haversine = "(6371 * acos(cos(radians({$lat})) * cos(radians(faskes.latittude)) * cos(radians(faskes.longitude) - radians({$lng})) + sin(radians({$lat})) * sin(radians(faskes.latittude))))";
            
            $query->select('faskes.*')
                ->selectRaw("{$haversine} AS distance_km")
                ->whereNotNull('faskes.latittude')
                ->whereNotNull('faskes.longitude');

            if (isset($params['radius'])) {
                $query->having('distance_km', '<=', (float)$radius);
            }

            $query->orderBy('distance_km', 'asc');
        } else {
            $query->select('faskes.*')->orderBy('faskes.id_faskes', 'asc');
        }

        // 2. Filter Berdasarkan Kota atau Kabupaten (Poin 2)
        if (!empty($params['kota_kabupaten'])) {
            $query->where('faskes.kota_kabupaten', 'like', "%{$params['kota_kabupaten']}%");
        }

        // 3. Filter Berdasarkan Spesialisasi Tertentu (Poin 3)
        // Menerima nama spesialisasi (misal: "Anak", "Jantung", "Penyakit Dalam")
        if (!empty($params['spesialisasi'])) {
            $spesialisasi = $params['spesialisasi'];
            $query->join('spesialisasi_faskes', 'faskes.id_faskes', '=', 'spesialisasi_faskes.id_faskes')
                  ->join('spesialisasi', 'spesialisasi_faskes.id_spesialisasi', '=', 'spesialisasi.id_spesialisasi')
                  ->where('spesialisasi.nama_spesialisasi', 'like', "%{$spesialisasi}%");
        }

        // 4. Filter Berdasarkan Fasilitas Penunjang Tertentu (Poin 4)
        // Menerima nama fasilitas (misal: "IGD", "Laboratorium", "Radiologi", "ICU")
        if (!empty($params['fasilitas'])) {
            $fasilitas = $params['fasilitas'];
            $query->join('fasilitas_faskes', 'faskes.id_faskes', '=', 'fasilitas_faskes.id_faskes')
                  ->join('fasilitas', 'fasilitas_faskes.id_fasilitas', '=', 'fasilitas.id_fasilitas')
                  ->where('fasilitas.nama_fasilitas', 'like', "%{$fasilitas}%");
        }

        // 5. Filter Support BPJS (Poin 6)
        if (isset($params['is_support_bpjs']) && $params['is_support_bpjs'] !== '') {
            $bpjsVal = filter_var($params['is_support_bpjs'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            $query->where('faskes.is_support_bpjs', $bpjsVal);
        }

        // 7. Filter Kelas Faskes (Poin 7 - misal: Kelas A, B, C, D)
        if (!empty($params['kelas'])) {
            $query->where('faskes.kelas', 'like', "%{$params['kelas']}%");
        }

        // 8. Filter Jenis Faskes (Poin 8 - Rumah Sakit, Klinik, Puskesmas)
        if (!empty($params['jenis_faskes'])) {
            $jenis = $params['jenis_faskes'];
            $query->where('faskes.jenis_faskes', 'like', "%{$jenis}%");
        }

        // Batasi hasil maksimal untuk AI / Filtering (default 10)
        $limit = min((int)($params['limit'] ?? 10), 50);
        $faskesList = $query->distinct()->limit($limit)->get();

        // Transformasi tambahan (slug, rating, dll)
        $faskesList->transform(function ($item) {
            $item->type_slug = $this->getTypeSlug($item->jenis_faskes);
            $item->nama_faskes = $this->formatFaskesName($item->nama_faskes, $item->type_slug, $item->jenis_faskes);
            return $item;
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Data faskes berhasil ditemukan',
            'total' => $faskesList->count(),
            'data' => $faskesList
        ]);
    }
}
