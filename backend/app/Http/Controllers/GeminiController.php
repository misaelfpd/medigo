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
  * Setiap faskes menampilkan informasi alamat, kontak telepon, kelas, daftar fasilitas medis (seperti IGD 24 Jam, Rawat Inap, Laboratorium, Farmasi), daftar dokter spesialis (seperti Spesialis Anak, Penyakit Dalam, Gigi, Kandungan), serta tautan Google Maps untuk navigasi.
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
2. FASKES TERDEKAT: Jika user menanyakan faskes terdekat dan terdapat info lokasi pengguna, sebutkan nama faskes terdekat, jaraknya (dalam km), alamat, nomor telepon/kontak, dan status penerimaan BPJS-nya dengan jelas.
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
        $request->validate([
            'message' => 'required|string|max:2000',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
        ]);

        if (! $gemini->isConfigured()) {
            return response()->json(['message' => self::UNAVAILABLE], 503);
        }

        $userMessage = $request->input('message');
        $lat = $request->filled('lat') ? (float) $request->input('lat') : ($request->filled('latitude') ? (float) $request->input('latitude') : null);
        $lng = $request->filled('lng') ? (float) $request->input('lng') : ($request->filled('longitude') ? (float) $request->input('longitude') : null);
        $user = $request->user();
        $userId = $user ? $user->id_user : null;

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

        // Sisipkan info lokasi ke pesan Gemini jika koordinat dikirim
        $messageForGemini = $userMessage;
        if (! empty($lat) && ! empty($lng)) {
            $messageForGemini .= "\n\n[INFO SISTEM: Titik koordinat GPS pengguna saat ini adalah Latitude: {$lat}, Longitude: {$lng}. Jika pengguna bertanya faskes terdekat, langsung gunakan koordinat ini pada fungsi cari_faskes.]";
        }

        $contents[] = ['role' => 'user', 'parts' => [['text' => $messageForGemini]]];

        try {
            [$aiReply, $faskesResults] = $this->converseWithFaskes($gemini, $contents, $lat, $lng);
        } catch (RuntimeException $e) {
            Log::error('Gemini chat failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => self::UNAVAILABLE], 502);
        }

        if ($userId) {
            DB::transaction(function () use ($userId, $userMessage, $aiReply) {
                // Simpan pesan asli pengguna ke database tanpa teks sistem
                GeminiChat::create(['id_user' => $userId, 'role' => 'user', 'message' => $userMessage]);
                GeminiChat::create(['id_user' => $userId, 'role' => 'model', 'message' => $aiReply]);
            });
        }

        $res = [
            'message' => 'Success',
            'data' => $aiReply,
        ];
        if (! empty($faskesResults)) {
            $res['faskes'] = $faskesResults;
        }

        return response()->json($res);
    }

    private function converseWithFaskes(GeminiClient $gemini, array $contents, ?float $userLat = null, ?float $userLng = null): array
    {
        $model = null;
        $lastFaskesResults = [];

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

                return [trim($text), $lastFaskesResults];
            }

            $toolResponses = [];
            foreach ($calls as $call) {
                $toolResponse = $this->runTool($call['functionCall'], $userLat, $userLng);
                if (isset($toolResponse['faskes'])) {
                    $lastFaskesResults = $toolResponse['faskes'];
                }
                $toolResponses[] = [
                    'functionResponse' => array_filter([
                        'id' => $call['functionCall']['id'] ?? null,
                        'name' => $call['functionCall']['name'],
                        'response' => $toolResponse,
                    ]),
                ];
            }

            $contents[] = ['role' => 'model', 'parts' => $parts];
            $contents[] = ['role' => 'user', 'parts' => $toolResponses];
        }

        throw new RuntimeException('Gemini kept calling tools without answering');
    }

    private function runTool(array $call, ?float $userLat = null, ?float $userLng = null): array
    {
        if ($call['name'] !== 'cari_faskes') {
            return ['error' => "Fungsi {$call['name']} tidak tersedia."];
        }

        $args = $call['args'] ?? [];
        if (empty($args['lat']) && $userLat !== null) {
            $args['lat'] = $userLat;
        }
        if (empty($args['lng']) && $userLng !== null) {
            $args['lng'] = $userLng;
        }

        $results = $this->searchFaskesAdvanced($args);

        return $results->isEmpty()
            ? ['message' => 'Faskes dengan kriteria tersebut tidak ditemukan di database Medigo.']
            : ['faskes' => $results->values()->all()];
    }

    private function payload(array $contents, ?float $userLat = null, ?float $userLng = null): array
    {
        $systemText = self::SYSTEM_PROMPT;
        if ($userLat !== null && $userLng !== null) {
            $systemText .= "\n\n[Lokasi Pengguna Real-Time]: Pengguna saat ini berada di koordinat Latitude: {$userLat}, Longitude: {$userLng}. Jika pengguna menanyakan faskes/rumah sakit terdekat, prioritaskan pencarian terdekat dengan koordinat ini.";
        }

        return [
            'system_instruction' => ['parts' => [['text' => $systemText]]],
            'contents' => $contents,
            'tools' => [[
                'function_declarations' => [[
                    'name' => 'cari_faskes',
                    'description' => 'Mencari data fasilitas kesehatan (faskes) di database berdasarkan kriteria (nama, kota, spesialisasi, fasilitas, kelas, status BPJS, jarak).',
                    'parameters' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'keyword' => ['type' => 'STRING', 'description' => 'Kata kunci umum atau nama faskes.'],
                            'kota_kabupaten' => ['type' => 'STRING', 'description' => 'Filter kota/kabupaten.'],
                            'jenis_faskes' => ['type' => 'STRING', 'description' => 'Jenis faskes (Rumah Sakit, Klinik, Puskesmas).'],
                            'spesialisasi' => ['type' => 'STRING', 'description' => 'Spesialisasi dokter.'],
                            'fasilitas' => ['type' => 'STRING', 'description' => 'Fasilitas medis.'],
                            '_support_bpjs' => ['type' => 'BOOLEAN', 'description' => 'Set true jika mencari faskes BPJS.'],
                            'kelas' => ['type' => 'STRING', 'description' => 'Kelas RS (A, B, C, D).'],
                            'lat' => ['type' => 'NUMBER', 'description' => 'Latitude user.'],
                            'lng' => ['type' => 'NUMBER', 'description' => 'Longitude user.'],
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
            'faskes.alamat_faskes', 'faskes.telepon', 'faskes.kecamatan', 'faskes.kota_kabupaten',
            'faskes.provinsi', 'faskes.kelas', 'faskes._support_bpjs', 'faskes.link_maps',
            'faskes.latitude', 'faskes.longitude',
        ]);

        $lat = ! empty($args['lat']) ? (float) $args['lat'] : null;
        $lng = ! empty($args['lng']) ? (float) $args['lng'] : null;

        if (! empty($args['kota_kabupaten'])) {
            $query->where('faskes.kota_kabupaten', 'like', "%{$args['kota_kabupaten']}%");
        }

        if (! empty($args['jenis_faskes'])) {
            $query->where('faskes.jenis_faskes', 'like', "%{$args['jenis_faskes']}%");
        }

        if (! empty($args['kelas'])) {
            $query->where('faskes.kelas', 'like', "%{$args['kelas']}%");
        }

        if (isset($args['_support_bpjs'])) {
            $query->where('faskes._support_bpjs', (bool) $args['_support_bpjs']);
        }

        if (! empty($args['spesialisasi'])) {
            $spec = $args['spesialisasi'];
            $query->whereExists(function ($sub) use ($spec) {
                $sub->select(DB::raw(1))
                    ->from('spesialisasi_faskes')
                    ->join('spesialisasi', 'spesialisasi.id_spesialisasi', '=', 'spesialisasi_faskes.id_spesialisasi')
                    ->whereColumn('spesialisasi_faskes.id_faskes', 'faskes.id_faskes')
                    ->where('spesialisasi.nama_spesialisasi', 'like', "%{$spec}%");
            });
        }

        if (! empty($args['fasilitas'])) {
            $facility = $args['fasilitas'];
            $query->whereExists(function ($sub) use ($facility) {
                $sub->select(DB::raw(1))
                    ->from('fasilitas_faskes')
                    ->join('fasilitas', 'fasilitas_faskes.id_fasilitas', '=', 'fasilitas.id_fasilitas')
                    ->whereColumn('fasilitas_faskes.id_faskes', 'faskes.id_faskes')
                    ->where('fasilitas.nama_fasilitas', 'like', "%{$facility}%");
            });
        }

        if (! empty($args['keyword'])) {
            $cleanKeyword = trim($args['keyword']);
            $words = array_values(array_filter(
                preg_split('/\s+/', mb_strtolower($cleanKeyword)),
                fn ($w) => ! in_array($w, ['dekat', 'terdekat', 'sekitar', 'di', 'dari', 'sini', 'saya', 'lokasi', 'ada', 'apakah'], true) && $w !== ''
            ));

            if (! empty($words)) {
                foreach (array_slice($words, 0, 5) as $word) {
                    $pattern = '%'.addcslashes($word, '\\%_').'%';
                    $query->where(function ($q) use ($pattern) {
                        foreach (['faskes.nama_faskes', 'faskes.jenis_faskes', 'faskes.kota_kabupaten', 'faskes.kecamatan', 'faskes.alamat_faskes'] as $column) {
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
            }
        }

        $limit = ($lat !== null && $lng !== null) ? 25 : 5;
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

        $items->each(function ($item) use ($fasilitasMap, $spesialisasiMap, $lat, $lng) {
            $item->fasilitas = $fasilitasMap->get($item->id_faskes, []);
            $item->spesialisasi = $spesialisasiMap->get($item->id_faskes, []);

            if (! $item->link_maps) {
                $mapsQuery = urlencode(implode(', ', array_filter([$item->nama_faskes, $item->alamat_faskes, $item->kota_kabupaten])));
                $item->link_maps = ($item->latitude && $item->longitude)
                    ? "https://www.google.com/maps/search/{$mapsQuery}/@{$item->latitude},{$item->longitude},17z"
                    : "https://www.google.com/maps/search/?api=1&query={$mapsQuery}";
            }

            if ($lat !== null && $lng !== null && $item->latitude !== null && $item->longitude !== null) {
                $dLat = deg2rad((float) $item->latitude - $lat);
                $dLng = deg2rad((float) $item->longitude - $lng);
                $a = sin($dLat / 2) * sin($dLat / 2) +
                    cos(deg2rad($lat)) * cos(deg2rad((float) $item->latitude)) *
                    sin($dLng / 2) * sin($dLng / 2);
                $c = 2 * atan2(sqrt(max(0.0, min(1.0, $a))), sqrt(max(0.0, min(1.0, 1 - $a))));
                $jarak = round(6371 * $c, 2);
                $item->distance_km = $jarak;
                $item->jarak_km = $jarak;
                $item->jarak_teks = "{$jarak} km dari lokasimu";
            }
        });

        if ($lat !== null && $lng !== null) {
            $items = $items->sortBy(fn ($item) => $item->distance_km ?? 999999)->values();
        }

        return $items->take(5);
    }
}
