<?php

namespace App\Http\Controllers;

use App\Models\Faskes;
use App\Services\RoadDistanceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FaskesController extends Controller
{
    /**
     * Map faskes type to a frontend-friendly slug.
     */
    public static function getTypeSlug($jenis_faskes)
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

    public static function getCityCoordinates(?string $kota): ?array
    {
        if (empty($kota)) {
            return null;
        }
        $k = strtolower(trim($kota));
        $cities = [
            'jakarta pusat' => [-6.1805, 106.8284],
            'jakarta selatan' => [-6.2615, 106.8106],
            'jakarta barat' => [-6.1674, 106.7637],
            'jakarta utara' => [-6.1214, 106.7741],
            'jakarta timur' => [-6.2250, 106.9004],
            'jakarta' => [-6.2088, 106.8456],
            'dki jakarta' => [-6.2088, 106.8456],
            'bekasi' => [-6.2383, 106.9756],
            'kota bekasi' => [-6.2383, 106.9756],
            'kabupaten bekasi' => [-6.2415, 107.1453],
            'bogor' => [-6.5971, 106.8060],
            'kota bogor' => [-6.5971, 106.8060],
            'kabupaten bogor' => [-6.4797, 106.8249],
            'depok' => [-6.4025, 106.7942],
            'kota depok' => [-6.4025, 106.7942],
            'tangerang' => [-6.1783, 106.6319],
            'kota tangerang' => [-6.1783, 106.6319],
            'tangerang selatan' => [-6.2885, 106.7179],
            'kabupaten tangerang' => [-6.1751, 106.4673],
            'bandung' => [-6.9175, 107.6191],
            'kota bandung' => [-6.9175, 107.6191],
            'kabupaten bandung' => [-7.0253, 107.5198],
            'surabaya' => [-7.2575, 112.7521],
            'semarang' => [-6.9667, 110.4167],
            'yogyakarta' => [-7.7956, 110.3695],
            'sleman' => [-7.7167, 110.3556],
            'bantul' => [-7.8939, 110.3275],
            'medan' => [3.5952, 98.6722],
            'palembang' => [-2.9761, 104.7754],
            'makassar' => [-5.1477, 119.4327],
            'denpasar' => [-8.6705, 115.2126],
            'badung' => [-8.5819, 115.1771],
            'bali' => [-8.4095, 115.1889],
            'malang' => [-7.9666, 112.6326],
            'surakarta' => [-7.5755, 110.8243],
            'solo' => [-7.5755, 110.8243],
            'cirebon' => [-6.7320, 108.5523],
            'sukabumi' => [-6.9277, 106.9300],
            'karawang' => [-6.3073, 107.3019],
            'serang' => [-6.1104, 106.1640],
            'cilegon' => [-6.0174, 106.0538],
            'lampung' => [-5.4500, 105.2667],
            'bandar lampung' => [-5.4500, 105.2667],
            'padang' => [-0.9471, 100.4172],
            'pekanbaru' => [0.5071, 101.4478],
            'batam' => [1.1301, 104.0529],
            'pontianak' => [-0.0263, 109.3425],
            'banjarmasin' => [-3.3194, 114.5908],
            'balikpapan' => [-1.2379, 116.8529],
            'samarinda' => [-0.5022, 117.1537],
            'manado' => [1.4748, 124.8421],
            'mataram' => [-8.5833, 116.1167],
            'kupang' => [-10.1772, 123.6070],
            'ambon' => [-3.6547, 128.1906],
            'jayapura' => [-2.5916, 140.6690],
        ];

        foreach ($cities as $cityKey => $coords) {
            if (str_contains($k, $cityKey) || str_contains($cityKey, $k)) {
                return $coords;
            }
        }

        return null;
    }

    public static function formatFaskesName($name, $typeSlug, $jenis_faskes = '')
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

            // Haversine formula (km) dengan perlindungan NULL & domain acos [-1, 1]
            $haversine = "(CASE WHEN faskes.latitude IS NOT NULL AND faskes.longitude IS NOT NULL THEN (6371 * acos(least(1.0, greatest(-1.0, cos(radians({$lat})) * cos(radians(faskes.latitude)) * cos(radians(faskes.longitude) - radians({$lng})) + sin(radians({$lat})) * sin(radians(faskes.latitude)))))) ELSE NULL END)";

            $query->select('faskes.*')
                ->selectRaw("{$haversine} AS distance_km");

            // Filter radius jika diberikan
            if ($request->filled('radius')) {
                $radius = (float) $request->radius;
                $query->whereNotNull('faskes.latitude')
                    ->whereNotNull('faskes.longitude')
                    ->having('distance_km', '<=', $radius);
            }

            $query->orderByRaw('(CASE WHEN distance_km IS NOT NULL THEN 0 ELSE 1 END) ASC, distance_km ASC, faskes.id_faskes ASC');
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

            $thumbnails = FotoFaskesController::thumbnailUrls($faskesIds);

            $faskes->transform(function ($item) use ($comments, $thumbnails) {
                $item->thumbnail_url = $thumbnails[$item->id_faskes] ?? null;
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

            if ($request->filled('lat') && $request->filled('lng')) {
                $faskes = RoadDistanceService::applyToCollection($faskes, (float) $request->lat, (float) $request->lng);
            }

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

        $thumbnails = FotoFaskesController::thumbnailUrls($faskesIds);

        $paginated->getCollection()->transform(function ($item) use ($comments, $thumbnails) {
            $item->thumbnail_url = $thumbnails[$item->id_faskes] ?? null;
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

        if ($request->filled('lat') && $request->filled('lng')) {
            $paginated->setCollection(
                RoadDistanceService::applyToCollection($paginated->getCollection(), (float) $request->lat, (float) $request->lng)
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil mengambil data faskes',
            'data' => $paginated,
        ]);
    }

    // 2. Ambil detail spesifik satu Faskes (buat halaman detail)
    public function show(Request $request, $id)
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

        // Hitung jarak (distance) jika koordinat user dikirimkan
        $lat = $request->filled('lat') ? (float) $request->input('lat') : ($request->filled('latitude') ? (float) $request->input('latitude') : null);
        $lng = $request->filled('lng') ? (float) $request->input('lng') : ($request->filled('longitude') ? (float) $request->input('longitude') : null);

        if ($lat !== null && $lng !== null && ! empty($faskes->latitude) && ! empty($faskes->longitude)) {
            $applied = RoadDistanceService::applyToCollection(collect([$faskes]), $lat, $lng, false)->first();
            if ($applied) {
                $faskes = $applied;
            }
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

        // 3. Ambil daftar fasilitas medis yang terhubung ke faskes ini
        $fasilitas = DB::table('fasilitas_faskes')
            ->join('fasilitas', 'fasilitas.id_fasilitas', '=', 'fasilitas_faskes.id_fasilitas')
            ->where('fasilitas_faskes.id_faskes', $id)
            ->select('fasilitas.id_fasilitas', 'fasilitas.nama_fasilitas')
            ->orderBy('fasilitas.nama_fasilitas', 'asc')
            ->get();

        // 4. Ambil daftar spesialisasi dokter yang terhubung ke faskes ini
        $spesialisasi = DB::table('spesialisasi_faskes')
            ->join('spesialisasi', 'spesialisasi.id_spesialisasi', '=', 'spesialisasi_faskes.id_spesialisasi')
            ->where('spesialisasi_faskes.id_faskes', $id)
            ->select('spesialisasi.id_spesialisasi', 'spesialisasi.nama_spesialisasi')
            ->orderBy('spesialisasi.nama_spesialisasi', 'asc')
            ->get();

        // Pasang juga ke objek $faskes untuk backward compatibility
        $faskes->fasilitas = $fasilitas;
        $faskes->spesialisasi = $spesialisasi;

        // Foto faskes: thumbnail (kartu beranda), banner (atas halaman detail), galeri (bawah banner)
        $foto = FotoFaskesController::photosFor((int) $faskes->id_faskes);
        $faskes->thumbnail_url = $foto['thumbnail']['url'] ?? null;
        $faskes->banner_url = $foto['banner']['url'] ?? null;
        $faskes->foto = $foto;

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil mengambil detail faskes',
            'data' => [
                'faskes' => $faskes,
                'distance' => $faskes->distance ?? null,
                'distance_km' => $faskes->distance_km ?? null,
                'jarak_km' => $faskes->jarak_km ?? null,
                'jarak_teks' => $faskes->jarak_teks ?? null,
                'reviews' => $reviews,
                'fasilitas' => $fasilitas,
                'spesialisasi' => $spesialisasi,
                'foto' => $foto,
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
            'telepon' => 'nullable|string|max:50',
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
        }

        // Fallback default coordinates from city dictionary if not provided
        if ($lat === null || $lng === null) {
            $cityCoords = self::getCityCoordinates($kotaKab);
            if ($cityCoords) {
                $lat = $cityCoords[0];
                $lng = $cityCoords[1];
            }
        }

        if (empty($linkMaps) || $linkMaps === '-' || $linkMaps === 'TRUE' || $linkMaps === 'FALSE') {
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
            'telepon' => $request->filled('telepon') ? trim($request->telepon) : null,
            'link_maps' => $linkMaps,
            'latitude' => $lat,
            'longitude' => $lng,
            '_support_bpjs' => $isBpjs,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Simpan Fasilitas Faskes jika dikirimkan
        if ($request->has('fasilitas')) {
            $fasilitasInput = $request->input('fasilitas');
            if (is_string($fasilitasInput)) {
                $decoded = json_decode($fasilitasInput, true);
                $fasilitasInput = is_array($decoded) ? $decoded : explode(',', $fasilitasInput);
            }
            if (is_array($fasilitasInput)) {
                $fasData = [];
                foreach ($fasilitasInput as $fItem) {
                    $fId = is_numeric($fItem) ? (int) $fItem : null;
                    if (! $fId && is_string($fItem) && trim($fItem) !== '') {
                        $fRow = DB::table('fasilitas')->where('nama_fasilitas', trim($fItem))->first();
                        $fId = $fRow ? $fRow->id_fasilitas : DB::table('fasilitas')->insertGetId([
                            'nama_fasilitas' => trim($fItem),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                    if ($fId) {
                        $fasData[] = [
                            'id_faskes' => $id,
                            'id_fasilitas' => $fId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
                if (! empty($fasData)) {
                    DB::table('fasilitas_faskes')->insertOrIgnore($fasData);
                }
            }
        }

        // Simpan Spesialisasi Faskes jika dikirimkan
        if ($request->has('spesialisasi')) {
            $spesialisasiInput = $request->input('spesialisasi');
            if (is_string($spesialisasiInput)) {
                $decoded = json_decode($spesialisasiInput, true);
                $spesialisasiInput = is_array($decoded) ? $decoded : explode(',', $spesialisasiInput);
            }
            if (is_array($spesialisasiInput)) {
                $specData = [];
                foreach ($spesialisasiInput as $sItem) {
                    $sId = is_numeric($sItem) ? (int) $sItem : null;
                    if (! $sId && is_string($sItem) && trim($sItem) !== '') {
                        $sRow = DB::table('spesialisasi')->where('nama_spesialisasi', trim($sItem))->first();
                        $sId = $sRow ? $sRow->id_spesialisasi : DB::table('spesialisasi')->insertGetId([
                            'nama_spesialisasi' => trim($sItem),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                    if ($sId) {
                        $specData[] = [
                            'id_faskes' => $id,
                            'id_spesialisasi' => $sId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
                if (! empty($specData)) {
                    DB::table('spesialisasi_faskes')->insertOrIgnore($specData);
                }
            }
        }

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
            'telepon' => 'nullable|string|max:50',
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
        if ($request->has('telepon')) {
            $updateData['telepon'] = $request->filled('telepon') ? trim($request->telepon) : null;
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
        } elseif ($targetLat !== null) {
            $updateData['latitude'] = $targetLat;
        } else {
            $kotaCheck = $request->kota_kabupaten ?? $faskes->kota_kabupaten;
            $cityCoords = self::getCityCoordinates($kotaCheck);
            if ($cityCoords) {
                $updateData['latitude'] = $cityCoords[0];
            }
        }

        if ($request->has('longitude')) {
            $updateData['longitude'] = $request->filled('longitude') ? (float) $request->longitude : null;
        } elseif ($targetLng !== null) {
            $updateData['longitude'] = $targetLng;
        } else {
            $kotaCheck = $request->kota_kabupaten ?? $faskes->kota_kabupaten;
            $cityCoords = self::getCityCoordinates($kotaCheck);
            if ($cityCoords) {
                $updateData['longitude'] = $cityCoords[1];
            }
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

        // Update Fasilitas Faskes jika dikirimkan
        if ($request->has('fasilitas')) {
            $fasilitasInput = $request->input('fasilitas');
            if (is_string($fasilitasInput)) {
                $decoded = json_decode($fasilitasInput, true);
                $fasilitasInput = is_array($decoded) ? $decoded : explode(',', $fasilitasInput);
            }
            if (is_array($fasilitasInput)) {
                DB::table('fasilitas_faskes')->where('id_faskes', $id)->delete();
                $fasData = [];
                foreach ($fasilitasInput as $fItem) {
                    $fId = is_numeric($fItem) ? (int) $fItem : null;
                    if (! $fId && is_string($fItem) && trim($fItem) !== '') {
                        $fRow = DB::table('fasilitas')->where('nama_fasilitas', trim($fItem))->first();
                        $fId = $fRow ? $fRow->id_fasilitas : DB::table('fasilitas')->insertGetId([
                            'nama_fasilitas' => trim($fItem),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                    if ($fId) {
                        $fasData[] = [
                            'id_faskes' => $id,
                            'id_fasilitas' => $fId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
                if (! empty($fasData)) {
                    DB::table('fasilitas_faskes')->insertOrIgnore($fasData);
                }
            }
        }

        // Update Spesialisasi Faskes jika dikirimkan
        if ($request->has('spesialisasi')) {
            $spesialisasiInput = $request->input('spesialisasi');
            if (is_string($spesialisasiInput)) {
                $decoded = json_decode($spesialisasiInput, true);
                $spesialisasiInput = is_array($decoded) ? $decoded : explode(',', $spesialisasiInput);
            }
            if (is_array($spesialisasiInput)) {
                DB::table('spesialisasi_faskes')->where('id_faskes', $id)->delete();
                $specData = [];
                foreach ($spesialisasiInput as $sItem) {
                    $sId = is_numeric($sItem) ? (int) $sItem : null;
                    if (! $sId && is_string($sItem) && trim($sItem) !== '') {
                        $sRow = DB::table('spesialisasi')->where('nama_spesialisasi', trim($sItem))->first();
                        $sId = $sRow ? $sRow->id_spesialisasi : DB::table('spesialisasi')->insertGetId([
                            'nama_spesialisasi' => trim($sItem),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                    if ($sId) {
                        $specData[] = [
                            'id_faskes' => $id,
                            'id_spesialisasi' => $sId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
                if (! empty($specData)) {
                    DB::table('spesialisasi_faskes')->insertOrIgnore($specData);
                }
            }
        }

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

        // Data foto ikut terhapus lewat cascade, file WebP-nya dihapus di sini
        FotoFaskesController::deleteAllFiles((int) $id);

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
     * @param  array  $params  Parameter pencarian (bisa dari $request->all() atau dari AI Function Call)
     */
    public function getFilterOptions(): JsonResponse
    {
        $kota = DB::table('faskes')
            ->whereNotNull('kota_kabupaten')
            ->where('kota_kabupaten', '!=', '')
            ->distinct()
            ->orderBy('kota_kabupaten', 'asc')
            ->pluck('kota_kabupaten');

        $kelas = DB::table('faskes')
            ->whereNotNull('kelas')
            ->where('kelas', '!=', '')
            ->distinct()
            ->orderBy('kelas', 'asc')
            ->pluck('kelas');

        $fasilitas = DB::table('fasilitas')
            ->orderBy('nama_fasilitas', 'asc')
            ->get(['id_fasilitas', 'nama_fasilitas']);

        $spesialisasi = DB::table('spesialisasi')
            ->orderBy('nama_spesialisasi', 'asc')
            ->get(['id_spesialisasi', 'nama_spesialisasi']);

        return response()->json([
            'status' => 'success',
            'data' => [
                'kota' => $kota,
                'kelas' => $kelas,
                'fasilitas' => $fasilitas,
                'spesialisasi' => $spesialisasi,
            ],
        ]);
    }

    public function searchFaskes(Request $request)
    {
        // Mendukung input dari Request HTTP biasa maupun array langsung dari AI Function Calling
        $params = $request->all();

        $query = DB::table('faskes');

        // 1. Filter Berdasarkan Daerah Saya (Koordinat GPS & Radius)
        $lat = $params['lat'] ?? null;
        $lng = $params['lng'] ?? null;

        if ($lat && $lng) {
            $latVal = (float) $lat;
            $lngVal = (float) $lng;
            $haversine = "(CASE WHEN faskes.latitude IS NOT NULL AND faskes.longitude IS NOT NULL THEN (6371 * acos(least(1.0, greatest(-1.0, cos(radians({$latVal})) * cos(radians(faskes.latitude)) * cos(radians(faskes.longitude) - radians({$lngVal})) + sin(radians({$latVal})) * sin(radians(faskes.latitude)))))) ELSE NULL END)";

            $query->select('faskes.*')
                ->selectRaw("{$haversine} AS distance_km");

            if (! empty($params['radius'])) {
                $query->whereNotNull('faskes.latitude')
                    ->whereNotNull('faskes.longitude')
                    ->having('distance_km', '<=', (float) $params['radius']);
            }

            $query->orderByRaw('(CASE WHEN distance_km IS NOT NULL THEN 0 ELSE 1 END) ASC, distance_km ASC, faskes.id_faskes DESC');
        } else {
            $query->select('faskes.*')->orderBy('faskes.id_faskes', 'desc');
        }

        // 2. Filter Keyword / Nama Faskes (Pencarian Nama, Alamat, atau Wilayah)
        if (! empty($params['q']) || ! empty($params['search']) || ! empty($params['nama']) || ! empty($params['nama_faskes'])) {
            $keyword = trim($params['q'] ?? ($params['search'] ?? ($params['nama'] ?? $params['nama_faskes'])));
            $cleanWords = array_values(array_filter(explode(' ', preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $keyword)), fn ($w) => mb_strlen(trim($w)) >= 2));

            $query->where(function ($sub) use ($keyword, $cleanWords) {
                $sub->where('faskes.nama_faskes', 'like', "%{$keyword}%")
                    ->orWhere('faskes.alamat_faskes', 'like', "%{$keyword}%")
                    ->orWhere('faskes.kota_kabupaten', 'like', "%{$keyword}%");

                if (count($cleanWords) > 1) {
                    $sub->orWhere(function ($wordSub) use ($cleanWords) {
                        foreach ($cleanWords as $w) {
                            $wordSub->where(function ($ws) use ($w) {
                                $ws->where('faskes.nama_faskes', 'like', "%{$w}%")
                                    ->orWhere('faskes.alamat_faskes', 'like', "%{$w}%")
                                    ->orWhere('faskes.kota_kabupaten', 'like', "%{$w}%");
                            });
                        }
                    });
                }
            });
        }

        // 3. Filter Berdasarkan Kota atau Kabupaten
        if (! empty($params['kota_kabupaten'])) {
            $query->where('faskes.kota_kabupaten', 'like', "%{$params['kota_kabupaten']}%");
        }

        // 4. Filter Berdasarkan Spesialisasi Tertentu
        if (! empty($params['spesialisasi'])) {
            $spesialisasiList = is_array($params['spesialisasi']) ? $params['spesialisasi'] : explode(',', $params['spesialisasi']);
            $spesialisasiList = array_filter(array_map('trim', $spesialisasiList));
            if (! empty($spesialisasiList)) {
                $query->whereExists(function ($sub) use ($spesialisasiList) {
                    $sub->select(DB::raw(1))
                        ->from('spesialisasi_faskes')
                        ->join('spesialisasi', 'spesialisasi_faskes.id_spesialisasi', '=', 'spesialisasi.id_spesialisasi')
                        ->whereColumn('spesialisasi_faskes.id_faskes', 'faskes.id_faskes')
                        ->where(function ($w) use ($spesialisasiList) {
                            foreach ($spesialisasiList as $sp) {
                                if (is_numeric($sp)) {
                                    $w->orWhere('spesialisasi.id_spesialisasi', (int) $sp);
                                } else {
                                    $w->orWhere('spesialisasi.nama_spesialisasi', 'like', "%{$sp}%");
                                }
                            }
                        });
                });
            }
        }

        // 5. Filter Berdasarkan Fasilitas Penunjang Tertentu
        if (! empty($params['fasilitas'])) {
            $fasilitasList = is_array($params['fasilitas']) ? $params['fasilitas'] : explode(',', $params['fasilitas']);
            $fasilitasList = array_filter(array_map('trim', $fasilitasList));
            if (! empty($fasilitasList)) {
                $query->whereExists(function ($sub) use ($fasilitasList) {
                    $sub->select(DB::raw(1))
                        ->from('fasilitas_faskes')
                        ->join('fasilitas', 'fasilitas_faskes.id_fasilitas', '=', 'fasilitas.id_fasilitas')
                        ->whereColumn('fasilitas_faskes.id_faskes', 'faskes.id_faskes')
                        ->where(function ($w) use ($fasilitasList) {
                            foreach ($fasilitasList as $fas) {
                                if (is_numeric($fas)) {
                                    $w->orWhere('fasilitas.id_fasilitas', (int) $fas);
                                } else {
                                    $w->orWhere('fasilitas.nama_fasilitas', 'like', "%{$fas}%");
                                }
                            }
                        });
                });
            }
        }

        // 6. Filter Support BPJS
        $bpjsInput = $params['is_support_bpjs'] ?? ($params['_support_bpjs'] ?? ($params['bpjs'] ?? null));
        if ($bpjsInput !== null && $bpjsInput !== '') {
            $bpjsVal = filter_var($bpjsInput, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            $query->where('faskes._support_bpjs', $bpjsVal);
        }

        // 7. Filter Kelas Faskes (Kelas A, B, C, D)
        if (! empty($params['kelas'])) {
            $query->where('faskes.kelas', 'like', "%{$params['kelas']}%");
        }

        // 8. Filter Jenis Faskes (Rumah Sakit, Klinik, Puskesmas)
        if (! empty($params['jenis_faskes'])) {
            $jenis = $params['jenis_faskes'];
            $query->where('faskes.jenis_faskes', 'like', "%{$jenis}%");
        }

        // Batasi hasil maksimal
        $limit = min((int) ($params['limit'] ?? 30), 100);
        $page = max((int) ($params['page'] ?? 1), 1);
        $offset = ($page - 1) * $limit;

        $total = $query->distinct()->count('faskes.id_faskes');
        $faskesList = $query->distinct()->offset($offset)->limit($limit)->get();

        $thumbnails = FotoFaskesController::thumbnailUrls($faskesList->pluck('id_faskes')->all());

        // Transformasi tambahan (slug, rating, dll)
        $faskesList->transform(function ($item) use ($thumbnails) {
            $item->thumbnail_url = $thumbnails[$item->id_faskes] ?? null;
            $item->type_slug = $this->getTypeSlug($item->jenis_faskes);
            $item->nama_faskes = $this->formatFaskesName($item->nama_faskes, $item->type_slug, $item->jenis_faskes);

            return $item;
        });

        if ($lat && $lng) {
            $faskesList = RoadDistanceService::applyToCollection($faskesList, (float) $lat, (float) $lng);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Data faskes berhasil ditemukan',
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'has_more' => ($page * $limit) < $total,
            'data' => $faskesList,
        ]);
    }
}
