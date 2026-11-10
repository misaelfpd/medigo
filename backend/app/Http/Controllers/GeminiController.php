<?php

namespace App\Http\Controllers;

use App\Models\Faskes;
use App\Models\GeminiChat;
use App\Services\GeminiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GeminiController extends Controller
{
    private const SYSTEM_PROMPT = <<<'PROMPT'
Kamu adalah Asisten Medigo bernama Medibot, asisten AI resmi dari platform kesehatan Medigo. Karaktermu ramah, empatik, santun, dan sangat memahami seluruh fitur website Medigo serta data fasilitas kesehatan.

TENTANG WEBSITE MEDIGO:
- Medigo adalah platform kesehatan digital yang memudahkan masyarakat mencari dan memilih fasilitas kesehatan (Rumah Sakit, Klinik, dan Puskesmas), berdiskusi seputar kesehatan di forum komunitas, serta membaca ulasan/rating nyata dari pasien.
- FITUR PENCARIAN FASKES:
  * Pengguna dapat mencari faskes berdasarkan nama, jenis (Rumah Sakit, Klinik, Puskesmas), kota/kabupaten, dan kecamatan.
  * Filter dukungan BPJS Kesehatan (menampilkan faskes yang melayani pasien BPJS).
  * Pencarian faskes terdekat berbasis GPS/koordinat real-time pengguna.
  * Setiap faskes menampilkan informasi alamat, kontak, kelas, daftar fasilitas medis (seperti IGD 24 Jam, Rawat Inap, Laboratorium, Farmasi), daftar dokter spesialis (seperti Spesialis Anak, Penyakit Dalam, Gigi, Kandungan), serta tautan Google Maps untuk navigasi.
- FITUR FORUM DISKUSI:
  * Pengguna yang sudah login dapat membuat topik diskusi/tanya-jawab kesehatan baru melalui menu 'Buat Diskusi' di halaman Forum Diskusi (/discussions).
  * Pengguna lain dapat memberikan tanggapan/komentar serta berbagi pengalaman medis.
  * Pengguna dapat melihat daftar diskusi milik sendiri di /user/discussions dan komentar sendiri di /user/comments.
  * Terdapat fitur pelaporan (Report) jika menemukan postingan atau tanggapan yang melanggar aturan, hoax, atau spam.
- FITUR ULASAN & RATING FASKES:
  * Pasien dapat memberikan rating bintang (1-5) dan review pengalaman berobat di halaman detail faskes.
  * Pengguna dapat melihat riwayat ulasannya di /user/reviews.
  * Tersedia fitur lapor komentar jika ada ulasan yang tidak pantas.

ATURAN PENTING & PANDUAN MENJAWAB:
1. PENCARIAN FASKES: Jika user mencari faskes, klinik, rumah sakit, puskesmas, dokter spesialis, fasilitas medis, atau menanyakan faskes TERDEKAT, SELALU gunakan fungsi `cari_faskes`.
2. FASKES TERDEKAT: Jika user menanyakan faskes terdekat dan terdapat info lokasi pengguna, sebutkan nama faskes terdekat, jaraknya (dalam km), alamat, dan status penerimaan BPJS-nya dengan jelas.
3. JIKA LOKASI BELUM TERSEDIA: Jika user meminta rekomendasi 'terdekat' namun tidak ada data koordinat lokasi, jelaskan bahwa pengguna dapat mengaktifkan izin lokasi di peramban/aplikasi atau menyebutkan nama kota/kecamatan mereka.
4. KONSULTASI MEDIS: Berikan edukasi dan saran medis awal yang menenangkan serta pertolongan pertama dasar, namun selalu ingatkan pengguna untuk memeriksakan diri langsung ke dokter atau faskes terdekat untuk penanganan dan diagnosis pasti.
5. PANDUAN WEBSITE: Jika user bertanya tentang cara menggunakan fitur website (seperti cara membuat diskusi, cara beri ulasan, cara lapor konten), pandu mereka dengan langkah-langkah yang jelas sesuai fitur Medigo di atas.
6. BAHASA: Gunakan bahasa Indonesia yang santai, komunikatif, ramah, dan penuh empati.
PROMPT;

    /** Chat turns from the last 24 hours sent back as memory. */
    private const HISTORY_LIMIT = 20;

    /** Stops a model that keeps calling tools from looping forever. */
    private const MAX_TOOL_ROUNDS = 3;

    private const UNAVAILABLE = 'Maaf, Asisten Medigo sedang tidak tersedia. Coba lagi beberapa saat lagi ya.';

    public function chat(Request $request, GeminiClient $gemini): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:2000',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);
        
        if (! $gemini->isConfigured()) {
            return response()->json(['message' => self::UNAVAILABLE], 503);
        }

        $user = $request->user();
        $userMessage = $validated['message'];
        $userLat = isset($validated['latitude']) ? (float) $validated['latitude'] : null;
        $userLng = isset($validated['longitude']) ? (float) $validated['longitude'] : null;

        // Cek apakah ada user yang login (opsional)
        $user = $request->user();
        $userId = $user ? $user->id_user : null;

        // Ambil history chat HANYA JIKA user sudah login (jika guest, history kosong dulu)
        $contents = [];
        if ($userId) {
            $contents = GeminiChat::where('id_user', $userId)
                ->where('created_at', '>=', now()->subDay())
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(self::HISTORY_LIMIT)
                ->get()
                ->reverse()
                ->map(fn (GeminiChat $chat) => ['role' => $chat->role, 'parts' => [['text' => $chat->message]]])
                ->values()
                ->all();
        }

        $contents[] = ['role' => 'user', 'parts' => [['text' => $userMessage]]];

        try {
            $aiReply = $this->converse($gemini, $contents, $userLat, $userLng);
        } catch (RuntimeException $e) {
            Log::error('Gemini chat failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => self::UNAVAILABLE], 502);
        }

        // Simpan ke database HANYA JIKA user login (agar tabel GeminiChat tidak error karena id_user null, kecuali kolom id_user di DB nullable)
        if ($userId) {
            DB::transaction(function () use ($userId, $userMessage, $aiReply) {
                GeminiChat::create(['id_user' => $userId, 'role' => 'user', 'message' => $userMessage]);
                GeminiChat::create(['id_user' => $userId, 'role' => 'model', 'message' => $aiReply]);
            });
        }

        return response()->json([
            'message' => 'Success',
            'data' => $aiReply,
        ]);
    }

    /**
     * Run the model, answering its function calls until it replies with text.
     */
    private function converse(GeminiClient $gemini, array $contents, ?float $userLat = null, ?float $userLng = null): string
    {
        $model = null; // Chosen on the first call, then kept for the follow-ups.

        for ($round = 0; $round <= self::MAX_TOOL_ROUNDS; $round++) {
            [$model, $data] = $gemini->generate($this->payload($contents, $userLat, $userLng), $model);

            $parts = $data['candidates'][0]['content']['parts'] ?? [];
            $calls = array_values(array_filter($parts, fn ($part) => isset($part['functionCall'])));

            if ($calls === []) {
                $text = collect($parts)
                    ->reject(fn ($part) => $part['thought'] ?? false)
                    ->pluck('text')
                    ->filter()
                    ->implode('');

                if (trim($text) === '') {
                    $reason = $data['candidates'][0]['finishReason'] ?? $data['promptFeedback']['blockReason'] ?? 'unknown';
                    throw new RuntimeException("Gemini returned no text (reason: {$reason})");
                }

                return trim($text);
            }

            // Send the model turn back unchanged: its parts carry thought signatures the API checks.
            $contents[] = ['role' => 'model', 'parts' => $parts];
            $contents[] = [
                'role' => 'user',
                'parts' => array_map(fn ($part) => ['functionResponse' => array_filter([
                    'id' => $part['functionCall']['id'] ?? null,
                    'name' => $part['functionCall']['name'],
                    'response' => $this->runTool($part['functionCall'], $userLat, $userLng),
                ])], $calls),
            ];
        }

        throw new RuntimeException('Gemini kept calling tools without answering');
    }

    private function runTool(array $call, ?float $userLat = null, ?float $userLng = null): array
    {
        if ($call['name'] !== 'cari_faskes') {
            return ['error' => "Fungsi {$call['name']} tidak tersedia."];
        }

        $args = $call['args'] ?? [];
        $keyword = trim((string) ($args['keyword'] ?? ''));
        $jenisFaskes = trim((string) ($args['jenis_faskes'] ?? ''));
        $hanyaBpjs = isset($args['hanya_bpjs']) ? (bool) $args['hanya_bpjs'] : null;
        $terdekat = (bool) ($args['terdekat'] ?? false);

        if (! $terdekat && ($userLat !== null && $userLng !== null)) {
            if (preg_match('/\b(dekat|terdekat|sekitar)\b/i', $keyword)) {
                $terdekat = true;
            }
        }

        $results = $this->searchFaskes($keyword, $userLat, $userLng, $terdekat, $jenisFaskes, $hanyaBpjs);

        return $results->isEmpty()
            ? ['message' => 'Faskes dengan kriteria tersebut tidak ditemukan di database Medigo.']
            : ['faskes' => $results->values()->all()];
    }

    /**
     * Hitung jarak dua koordinat GPS dengan Haversine Formula (dalam km).
     */
    private function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt(max(0.0, min(1.0, $a))), sqrt(max(0.0, min(1.0, 1 - $a))));

        return round($earthRadius * $c, 2);
    }

    /**
     * Cari faskes berdasarkan kata kunci nama, spesialisasi, fasilitas, lokasi atau faskes terdekat.
     */
    private function searchFaskes(
        string $keyword,
        ?float $userLat = null,
        ?float $userLng = null,
        bool $terdekat = false,
        string $jenisFaskes = '',
        ?bool $hanyaBpjs = null
    ) {
        $cleanKeyword = trim($keyword);

        $searchWords = array_values(array_filter(
            preg_split('/\s+/', mb_strtolower($cleanKeyword)),
            fn ($w) => ! in_array($w, ['dekat', 'terdekat', 'sekitar', 'di', 'dari', 'sini', 'saya', 'lokasi', 'ada', 'apakah'], true) && $w !== ''
        ));

        $query = Faskes::query()->select([
            'id_faskes', 'nama_faskes', 'jenis_faskes', 'alamat_faskes',
            'kecamatan', 'kota_kabupaten', 'provinsi', 'link_maps', '_support_bpjs',
            'latitude', 'longitude',
        ]);

        if ($hanyaBpjs !== null) {
            $query->where('_support_bpjs', $hanyaBpjs ? 1 : 0);
        }

        if ($jenisFaskes !== '') {
            $query->where('jenis_faskes', 'like', "%{$jenisFaskes}%");
        }

        if (! empty($searchWords)) {
            foreach (array_slice($searchWords, 0, 5) as $word) {
                $pattern = '%'.addcslashes($word, '\\%_').'%';
                $query->where(function ($q) use ($pattern) {
                    foreach (['nama_faskes', 'jenis_faskes', 'kota_kabupaten', 'kecamatan'] as $column) {
                        $q->orWhereRaw("LOWER({$column}) LIKE ? ESCAPE '\\'", [$pattern]);
                    }

                    $q->orWhereExists(function ($sub) use ($pattern) {
                        $sub->select(DB::raw(1))
                            ->from('spesialisasi_faskes')
                            ->join('spesialisasi', 'spesialisasi.id_spesialisasi', '=', 'spesialisasi_faskes.id_spesialisasi')
                            ->whereColumn('spesialisasi_faskes.id_faskes', 'faskes.id_faskes')
                            ->whereRaw("LOWER(spesialisasi.nama_spesialisasi) LIKE ? ESCAPE '\\'", [$pattern]);
                    });

                    $q->orWhereExists(function ($sub) use ($pattern) {
                        $sub->select(DB::raw(1))
                            ->from('fasilitas_faskes')
                            ->join('fasilitas', 'fasilitas.id_fasilitas', '=', 'fasilitas_faskes.id_fasilitas')
                            ->whereColumn('fasilitas_faskes.id_faskes', 'faskes.id_faskes')
                            ->whereRaw("LOWER(fasilitas.nama_fasilitas) LIKE ? ESCAPE '\\'", [$pattern]);
                    });
                });
            }
        } elseif ($cleanKeyword === '' && ! $terdekat) {
            return collect();
        }

        $limit = ($userLat !== null && $userLng !== null) ? 25 : 5;
        $items = $query->limit($limit)->get();

        if ($items->isEmpty()) {
            return $items;
        }

        $faskesIds = $items->pluck('id_faskes')->all();

        $fasilitasMap = DB::table('fasilitas_faskes')
            ->join('fasilitas', 'fasilitas.id_fasilitas', '=', 'fasilitas_faskes.id_fasilitas')
            ->whereIn('fasilitas_faskes.id_faskes', $faskesIds)
            ->select('fasilitas_faskes.id_faskes', 'fasilitas.nama_fasilitas')
            ->get()
            ->groupBy('id_faskes')
            ->map(fn ($group) => $group->pluck('nama_fasilitas')->all());

        $spesialisasiMap = DB::table('spesialisasi_faskes')
            ->join('spesialisasi', 'spesialisasi.id_spesialisasi', '=', 'spesialisasi_faskes.id_spesialisasi')
            ->whereIn('spesialisasi_faskes.id_faskes', $faskesIds)
            ->select('spesialisasi_faskes.id_faskes', 'spesialisasi.nama_spesialisasi')
            ->get()
            ->groupBy('id_faskes')
            ->map(fn ($group) => $group->pluck('nama_spesialisasi')->all());

        $items->each(function ($item) use ($fasilitasMap, $spesialisasiMap, $userLat, $userLng) {
            $item->fasilitas = $fasilitasMap->get($item->id_faskes, []);
            $item->spesialisasi = $spesialisasiMap->get($item->id_faskes, []);

            if (! $item->link_maps) {
                $mapsQuery = urlencode(implode(', ', array_filter([$item->nama_faskes, $item->alamat_faskes, $item->kota_kabupaten])));
                $item->link_maps = ($item->latitude && $item->longitude)
                    ? "https://www.google.com/maps/search/{$mapsQuery}/@{$item->latitude},{$item->longitude},17z"
                    : "https://www.google.com/maps/search/?api=1&query={$mapsQuery}";
            }

            if ($userLat !== null && $userLng !== null && $item->latitude !== null && $item->longitude !== null) {
                $jarak = $this->calculateDistance($userLat, $userLng, (float) $item->latitude, (float) $item->longitude);
                $item->jarak_km = $jarak;
                $item->jarak_teks = "{$jarak} km dari lokasimu";
            }
        });

        if ($userLat !== null && $userLng !== null) {
            $items = $items->sortBy(function ($item) {
                return $item->jarak_km ?? 999999;
            })->values();
        }

        return $items->take(5);
    }

    private function payload(array $contents, ?float $userLat = null, ?float $userLng = null): array
    {
        $systemText = self::SYSTEM_PROMPT;

        if ($userLat !== null && $userLng !== null) {
            $systemText .= "\n\n[Lokasi Pengguna Real-Time]: Pengguna saat ini berada di koordinat Latitude: {$userLat}, Longitude: {$userLng}. Jika pengguna menanyakan faskes/rumah sakit/klinik terdekat, prioritaskan pencarian terdekat dengan koordinat ini.";
        } else {
            $systemText .= "\n\n[Lokasi Pengguna]: Pengguna belum membagikan koordinat GPS saat ini. Jika pengguna meminta rekomendasi terdekat tanpa menyebutkan daerah, ingatkan dengan ramah bahwa pengguna dapat mengaktifkan izin lokasi di peramban/aplikasi atau menyebutkan kota/kecamatan mereka.";
        }

        return [
            'system_instruction' => ['parts' => [['text' => $systemText]]],
            'contents' => $contents,
            'tools' => [[
                'function_declarations' => [[
                    'name' => 'cari_faskes',
                    'description' => 'Mencari data fasilitas kesehatan (faskes) di database berdasarkan berbagai kriteria spesifik seperti kata kunci nama, kota, spesialisasi dokter, fasilitas penunjang, status BPJS, kelas RS, jenis faskes, atau jarak terdekat.',
                    'parameters' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'keyword' => [
                                'type' => 'STRING',
                                'description' => "Kata kunci pencarian, misalnya: nama faskes ('siloam'), jenis ('rumah sakit', 'klinik', 'puskesmas'), spesialisasi dokter ('gigi', 'anak', 'kandungan'), fasilitas ('igd', 'rawat inap'), atau lokasi ('bogor', 'jakarta').",
                            ],
                            'jenis_faskes' => [
                                'type' => 'STRING',
                                'description' => "Jenis faskes yang dicari jika spesifik: 'Rumah Sakit', 'Klinik', atau 'Puskesmas'.",
                            ],
                            'hanya_bpjs' => [
                                'type' => 'BOOLEAN',
                                'description' => 'Set true jika user secara khusus meminta faskes yang menerima BPJS Kesehatan.',
                            ],
                            'terdekat' => [
                                'type' => 'BOOLEAN',
                                'description' => 'Set true jika user meminta faskes yang paling dekat dari lokasinya saat ini.',
                            ],
                            'jenis_faskes' => [
                                'type' => 'STRING',
                                'description' => "Jenis faskes, misalnya: 'Rumah Sakit', 'Klinik', 'Puskesmas'."
                            ],
                            'spesialisasi' => [
                                'type' => 'STRING',
                                'description' => "Spesialisasi dokter yang dicari, misalnya: 'Anak', 'Jantung', 'Penyakit Dalam'."
                            ],
                            'fasilitas' => [
                                'type' => 'STRING',
                                'description' => "Fasilitas penunjang medis, misalnya: 'IGD', 'Laboratorium', 'Radiologi'."
                            ],
                            '_support_bpjs' => [
                                'type' => 'BOOLEAN',
                                'description' => "Set true jika hanya mencari faskes yang mendukung BPJS."
                            ],
                            'kelas' => [
                                'type' => 'STRING',
                                'description' => "Kelas rumah sakit, misalnya: 'A', 'B', 'C', 'D'."
                            ],
                            'lat' => [
                                'type' => 'NUMBER',
                                'description' => "Latitude posisi user (jika meminta faskes terdekat di daerah saya)."
                            ],
                            'lng' => [
                                'type' => 'NUMBER',
                                'description' => "Longitude posisi user."
                            ]
                        ],
                    ],
                ]],
            ]],
        ];
    }

    private function searchFaskesAdvanced(array $args)
    {
        $query = Faskes::query()->select([
            'faskes.id_faskes', 'faskes.nama_faskes', 'faskes.jenis_faskes', 
            'faskes.alamat_faskes', 'faskes.kecamatan', 'faskes.kota_kabupaten', 
            'faskes.provinsi', 'faskes.kelas', 'faskes._support_bpjs', 'faskes.link_maps'
        ]);

        // 1. Filter Jarak GPS (Terdekat di daerah saya)
        if (!empty($args['lat']) && !empty($args['lng'])) {
            $lat = (float) $args['lat'];
            $lng = (float) $args['lng'];
            $haversine = "(6371 * acos(cos(radians({$lat})) * cos(radians(faskes.latitude)) * cos(radians(faskes.longitude) - radians({$lng})) + sin(radians({$lat})) * sin(radians(faskes.latitude))))";
            
            $query->selectRaw("{$haversine} AS distance_km")
                  ->whereNotNull('faskes.latitude')
                  ->whereNotNull('faskes.longitude')
                  ->orderBy('distance_km', 'asc');
        }

        // 2. Filter Kota / Kabupaten
        if (!empty($args['kota_kabupaten'])) {
            $query->where('faskes.kota_kabupaten', 'like', "%{$args['kota_kabupaten']}%");
        }

        // 3. Filter Jenis Faskes
        if (!empty($args['jenis_faskes'])) {
            $query->where('faskes.jenis_faskes', 'like', "%{$args['jenis_faskes']}%");
        }

        // 4. Filter Kelas Faskes
        if (!empty($args['kelas'])) {
            $query->where('faskes.kelas', 'like', "%{$args['kelas']}%");
        }

        // 5. Filter Support BPJS
        if (isset($args['_support_bpjs'])) {
            $query->where('faskes._support_bpjs', (bool)$args['_support_bpjs']);
        }

        // 6. Filter Berdasarkan Spesialisasi (Relasi ke tabel spesialisasi_faskes)
        if (!empty($args['spesialisasi'])) {
            $spec = $args['spesialisasi'];
            $query->join('spesialisasi_faskes', 'faskes.id_faskes', '=', 'spesialisasi_faskes.id_faskes')
                  ->join('spesialisasi', 'spesialisasi_faskes.id_spesialisasi', '=', 'spesialisasi.id_spesialisasi')
                  ->where('spesialisasi.nama_spesialisasi', 'like', "%{$spec}%");
        }

        // 7. Filter Berdasarkan Fasilitas Penunjang (Relasi ke tabel fasilitas_faskes)
        if (!empty($args['fasilitas'])) {
            $facility = $args['fasilitas'];
            $query->join('fasilitas_faskes', 'faskes.id_faskes', '=', 'fasilitas_faskes.id_faskes')
                  ->join('fasilitas', 'fasilitas_faskes.id_fasilitas', '=', 'fasilitas.id_fasilitas')
                  ->where('fasilitas.nama_fasilitas', 'like', "%{$facility}%");
        }

        // 8. Keyword Bebas (Nama Faskes / Alamat)
        if (!empty($args['keyword'])) {
            $keyword = trim($args['keyword']);
            $query->where(function ($q) use ($keyword) {
                $q->where('faskes.nama_faskes', 'like', "%{$keyword}%")
                  ->orWhere('faskes.alamat_faskes', 'like', "%{$keyword}%");
            });
        }

        return $query->distinct()->limit(5)->get();
    }
}
