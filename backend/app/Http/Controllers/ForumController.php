<?php

namespace App\Http\Controllers;

use App\Services\RoadDistanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Fitur pendukung halaman forum:
 * - pencarian faskes untuk tombol mention di judul diskusi,
 * - rekomendasi faskes terdekat dari lokasi user (diurutkan jumlah like).
 */
class ForumController extends Controller
{
    /**
     * Public: cari faskes untuk di-mention di judul diskusi.
     * GET /api/faskes/mention-search?q=siloam&limit=10
     */
    public function mentionSearch(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'nullable|string|max:100',
            'limit' => 'nullable|integer|min:1|max:20',
        ]);

        $q = trim((string) $request->input('q', ''));
        $limit = (int) $request->input('limit', 10);

        $query = DB::table('faskes')->select([
            'id_faskes', 'nama_faskes', 'jenis_faskes', 'kota_kabupaten', 'kecamatan', 'alamat_faskes',
        ]);

        if ($q !== '') {
            $like = self::likeValue($q);
            $prefix = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $q).'%';

            $query->where(function ($w) use ($like) {
                $w->whereRaw("nama_faskes LIKE ? ESCAPE '!'", [$like])
                    ->orWhereRaw("kota_kabupaten LIKE ? ESCAPE '!'", [$like]);
            })
                // Nama yang diawali kata kunci tampil duluan
                ->orderByRaw("CASE WHEN nama_faskes LIKE ? ESCAPE '!' THEN 0 ELSE 1 END", [$prefix]);
        }

        $results = $query->orderBy('nama_faskes')->limit($limit)->get();
        $thumbnails = FotoFaskesController::thumbnailUrls($results->pluck('id_faskes')->all());

        return response()->json([
            'status' => 'success',
            'data' => $results->map(fn ($f) => self::faskesSummary($f, $thumbnails[$f->id_faskes] ?? null))->values(),
        ]);
    }

    /**
     * Public: rekomendasi faskes terdekat untuk halaman forum.
     * Ambil faskes dalam radius dari lokasi user, lalu pilih yang total like-nya terbanyak
     * (like dari diskusi aktif yang me-mention faskes tersebut). Kalau like sama, yang lebih dekat duluan.
     * Kalau tidak ada faskes dalam radius, radius diabaikan dan diambil yang paling dekat.
     *
     * GET /api/forum/faskes-terdekat?lat=-6.2&lng=106.8&radius=10&limit=5
     */
    public function nearbyFaskes(Request $request): JsonResponse
    {
        // Terima juga nama parameter latitude/longitude seperti endpoint lain
        $request->merge([
            'lat' => $request->input('lat', $request->input('latitude')),
            'lng' => $request->input('lng', $request->input('longitude')),
        ]);

        $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:0.5|max:50',
            'limit' => 'nullable|integer|min:1|max:20',
        ], [
            'lat.required' => 'Lokasi pengguna (lat) wajib dikirim.',
            'lng.required' => 'Lokasi pengguna (lng) wajib dikirim.',
        ]);

        $lat = (float) $request->input('lat');
        $lng = (float) $request->input('lng');
        $radius = (float) $request->input('radius', 10);
        $limit = (int) $request->input('limit', 5);

        $results = $this->nearbyQuery($lat, $lng, $limit, $radius)->get();
        $radiusExpanded = false;

        if ($results->isEmpty()) {
            $results = $this->nearbyQuery($lat, $lng, $limit, null)->get();
            $radiusExpanded = $results->isNotEmpty();
        }

        $ids = $results->pluck('id_faskes')->all();
        $thumbnails = FotoFaskesController::thumbnailUrls($ids);
        $ratings = DB::table('komentar')
            ->whereIn('id_faskes', $ids)
            ->select('id_faskes', DB::raw('ROUND(AVG(rating), 1) as avg_rating'), DB::raw('COUNT(id_komentar) as total_reviews'))
            ->groupBy('id_faskes')
            ->get()
            ->keyBy('id_faskes');

        $items = $results->map(function ($f) use ($thumbnails, $ratings) {
            $rating = $ratings[$f->id_faskes] ?? null;

            return (object) array_merge(self::faskesSummary($f, $thumbnails[$f->id_faskes] ?? null), [
                'alamat_faskes' => $f->alamat_faskes,
                'telepon' => $f->telepon,
                '_support_bpjs' => $f->_support_bpjs,
                'latitude' => $f->latitude,
                'longitude' => $f->longitude,
                'distance_km' => $f->distance_km !== null ? round((float) $f->distance_km, 1) : null,
                'total_like' => (int) $f->total_like,
                'total_diskusi' => (int) $f->total_diskusi,
                'avg_rating' => $rating && $rating->total_reviews > 0 ? (string) $rating->avg_rating : null,
                'total_reviews' => $rating ? (int) $rating->total_reviews : 0,
            ]);
        });

        // Jarak mengikuti rute jalan (OSRM) untuk ditampilkan; urutan tetap (like dulu, baru jarak)
        $items = RoadDistanceService::applyToCollection($items, $lat, $lng, false)->values();

        return response()->json([
            'status' => 'success',
            'message' => $items->isEmpty()
                ? 'Belum ada faskes dengan koordinat di sekitar lokasi Anda.'
                : 'Berhasil mengambil rekomendasi faskes terdekat',
            'radius_km' => $radius,
            'radius_diperluas' => $radiusExpanded,
            'data' => $items,
        ]);
    }

    private function nearbyQuery(float $lat, float $lng, int $limit, ?float $radius)
    {
        $distance = self::haversineSql($lat, $lng);

        $query = DB::table('faskes')
            ->whereNotNull('faskes.latitude')
            ->whereNotNull('faskes.longitude')
            ->select('faskes.*')
            ->selectRaw("{$distance} AS distance_km")
            ->selectRaw("(SELECT COUNT(*) FROM like_diskusi
                JOIN diskusi ON diskusi.id_diskusi = like_diskusi.id_diskusi
                WHERE diskusi.id_faskes_mention = faskes.id_faskes AND diskusi.status_diskusi = 'active') AS total_like")
            ->selectRaw("(SELECT COUNT(*) FROM diskusi
                WHERE diskusi.id_faskes_mention = faskes.id_faskes AND diskusi.status_diskusi = 'active') AS total_diskusi");

        if ($radius !== null) {
            // Bounding box dulu supaya rumus jarak tidak dihitung untuk semua faskes
            $latDelta = $radius / 111.0;
            $lngDelta = $radius / max(0.01, 111.0 * cos(deg2rad($lat)));
            $query->whereBetween('faskes.latitude', [$lat - $latDelta, $lat + $latDelta])
                ->whereBetween('faskes.longitude', [$lng - $lngDelta, $lng + $lngDelta])
                ->whereRaw("{$distance} <= ?", [$radius])
                ->orderByDesc('total_like');
        }

        return $query->orderBy('distance_km')->limit($limit);
    }

    /**
     * Rumus haversine (km). least/greatest menjaga nilai acos di [-1, 1];
     * SQLite (dipakai saat testing) memakai min/max.
     */
    public static function haversineSql(float $lat, float $lng): string
    {
        [$least, $greatest] = DB::connection()->getDriverName() === 'sqlite' ? ['min', 'max'] : ['least', 'greatest'];

        return "(6371 * acos({$least}(1.0, {$greatest}(-1.0, cos(radians({$lat})) * cos(radians(faskes.latitude)) * cos(radians(faskes.longitude) - radians({$lng})) + sin(radians({$lat})) * sin(radians(faskes.latitude))))))";
    }

    /**
     * Ringkasan faskes untuk mention / kartu kecil di forum.
     */
    public static function faskesSummary(object $f, ?string $thumbnailUrl = null): array
    {
        $typeSlug = FaskesController::getTypeSlug($f->jenis_faskes ?? '');

        return [
            'id_faskes' => (int) $f->id_faskes,
            'nama_faskes' => FaskesController::formatFaskesName($f->nama_faskes ?? '', $typeSlug, $f->jenis_faskes ?? ''),
            'jenis_faskes' => $f->jenis_faskes ?? null,
            'type_slug' => $typeSlug,
            'kota_kabupaten' => $f->kota_kabupaten ?? null,
            'kecamatan' => $f->kecamatan ?? null,
            'thumbnail_url' => $thumbnailUrl,
            'detail_path' => '/faskes/'.$f->id_faskes,
        ];
    }

    /**
     * Escape wildcard LIKE supaya input dicari apa adanya (dipakai dengan ESCAPE '!').
     */
    public static function likeValue(string $value): string
    {
        return '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], trim($value)).'%';
    }
}
