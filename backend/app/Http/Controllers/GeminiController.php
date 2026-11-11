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

    private const HISTORY_LIMIT = 20;
    private const MAX_TOOL_ROUNDS = 3;
    private const UNAVAILABLE = 'Maaf, Asisten Medigo sedang tidak tersedia. Coba lagi beberapa saat ya.';

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
        $lat = $request->input('lat');
        $lng = $request->input('lng');
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

        // SISIPKAN INFO LOKASI KE GEMINI
        $messageForGemini = $userMessage;
        if (!empty($lat) && !empty($lng)) {
            $messageForGemini .= "\n\n[INFO SISTEM: Titik koordinat GPS pengguna saat ini adalah Latitude: {$lat}, Longitude: {$lng}. Jika pengguna bertanya faskes terdekat, langsung gunakan koordinat ini pada fungsi cari_faskes.]";
        }

        $contents[] = ['role' => 'user', 'parts' => [['text' => $messageForGemini]]];

        try {
            [$aiReply, $faskesResults] = $this->converseWithFaskes($gemini, $contents);
        } catch (RuntimeException $e) {
            Log::error('Gemini chat failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => self::UNAVAILABLE], 502);
        }

        if ($userId) {
            DB::transaction(function () use ($userId, $userMessage, $aiReply) {
                // Yang disimpan ke DB adalah pesan asli user, tanpa teks [INFO SISTEM]
                GeminiChat::create(['id_user' => $userId, 'role' => 'user', 'message' => $userMessage]);
                GeminiChat::create(['id_user' => $userId, 'role' => 'model', 'message' => $aiReply]);
            });
        }

        return response()->json([
            'message' => 'Success',
            'data' => $aiReply,
            'faskes' => $faskesResults, 
        ]);
    }

    private function converseWithFaskes(GeminiClient $gemini, array $contents): array
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
                $toolResponse = $this->runTool($call['functionCall']);
                if (isset($toolResponse['faskes'])) {
                    $lastFaskesResults = $toolResponse['faskes'];
                }
                $toolResponses[] = [
                    'functionResponse' => array_filter([
                        'id' => $call['functionCall']['id'] ?? null,
                        'name' => $call['functionCall']['name'],
                        'response' => $toolResponse,
                    ])
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

    private function payload(array $contents): array
    {
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
                            'keyword' => ['type' => 'STRING', 'description' => "Kata kunci umum atau nama faskes."],
                            'kota_kabupaten' => ['type' => 'STRING', 'description' => "Filter kota/kabupaten."],
                            'jenis_faskes' => ['type' => 'STRING', 'description' => "Jenis faskes (Rumah Sakit, Klinik, Puskesmas)."],
                            'spesialisasi' => ['type' => 'STRING', 'description' => "Spesialisasi dokter."],
                            'fasilitas' => ['type' => 'STRING', 'description' => "Fasilitas medis."],
                            '_support_bpjs' => ['type' => 'BOOLEAN', 'description' => "Set true jika mencari faskes BPJS."],
                            'kelas' => ['type' => 'STRING', 'description' => "Kelas RS (A, B, C, D)."],
                            'lat' => ['type' => 'NUMBER', 'description' => "Latitude user."],
                            'lng' => ['type' => 'NUMBER', 'description' => "Longitude user."]
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
            'faskes.provinsi', 'faskes.kelas', 'faskes._support_bpjs', 'faskes.link_maps',
            'faskes.latitude', 'faskes.longitude',
        ]);

        // 1. Filter Jarak GPS (Terdekat di daerah saya) menggunakan formula Haversine
        if (!empty($args['lat']) && !empty($args['lng'])) {
            $lat = (float) $args['lat'];
            $lng = (float) $args['lng'];
            $haversine = "(6371 * acos(cos(radians({$lat})) * cos(radians(faskes.latitude)) * cos(radians(faskes.longitude) - radians({$lng})) + sin(radians({$lat})) * sin(radians(faskes.latitude))))";
            
            $query->selectRaw("{$haversine} AS distance_km")
                  ->whereNotNull('faskes.latitude')
                  ->whereNotNull('faskes.longitude')
                  ->orderBy('distance_km', 'asc');
        }

        if (!empty($args['kota_kabupaten'])) {
            $query->where('faskes.kota_kabupaten', 'like', "%{$args['kota_kabupaten']}%");
        }

        if (!empty($args['jenis_faskes'])) {
            $query->where('faskes.jenis_faskes', 'like', "%{$args['jenis_faskes']}%");
        }

        if (!empty($args['kelas'])) {
            $query->where('faskes.kelas', 'like', "%{$args['kelas']}%");
        }

        if (isset($args['_support_bpjs'])) {
            $query->where('faskes._support_bpjs', (bool) $args['_support_bpjs']);
        }

        if (!empty($args['spesialisasi'])) {
            $spec = $args['spesialisasi'];
            $query->whereExists(function ($sub) use ($spec) {
                $sub->select(DB::raw(1))
                    ->from('spesialisasi_faskes')
                    ->join('spesialisasi', 'spesialisasi_faskes.id_spesialisasi', '=', 'spesialisasi.id_spesialisasi')
                    ->whereColumn('spesialisasi_faskes.id_faskes', 'faskes.id_faskes')
                    ->where('spesialisasi.nama_spesialisasi', 'like', "%{$spec}%");
            });
        }

        if (!empty($args['fasilitas'])) {
            $facility = $args['fasilitas'];
            $query->whereExists(function ($sub) use ($facility) {
                $sub->select(DB::raw(1))
                    ->from('fasilitas_faskes')
                    ->join('fasilitas', 'fasilitas_faskes.id_fasilitas', '=', 'fasilitas.id_fasilitas')
                    ->whereColumn('fasilitas_faskes.id_faskes', 'faskes.id_faskes')
                    ->where('fasilitas.nama_fasilitas', 'like', "%{$facility}%");
            });
        }

        if (!empty($args['keyword'])) {
            $keyword = trim($args['keyword']);
            $query->where(function ($q) use ($keyword) {
                $q->where('faskes.nama_faskes', 'like', "%{$keyword}%")
                  ->orWhere('faskes.alamat_faskes', 'like', "%{$keyword}%");
            });
        }

        $results = $query->distinct()->limit(4)->get();

        // Format setiap item agar menyertakan link maps & alamat yang rapi
        return $results->map(function ($faskes) {
            $formattedAddress = $this->formatAddress(
                $faskes->alamat_faskes, 
                $faskes->kota_kabupaten, 
                $faskes->kecamatan ?? ''
            );

            // Set properti tambahan atau timpa link_maps dengan fungsi BerandaController
            $faskes->alamat_faskes = $formattedAddress;
            $faskes->link_maps = $this->getGoogleMapsUrl(
                $faskes->nama_faskes,
                $faskes->latitude ?? null,
                $faskes->longitude ?? null,
                $faskes->kota_kabupaten,
                $formattedAddress
            );

            return $faskes;
        });
    }

    /**
     * Memformat alamat faskes agar rapi dan konsisten
     */
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

        // Jika alamat belum ada awalan nama jalan dan terlihat nama jalan, tambahkan "Jl. "
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

    /**
     * Menghasilkan URL Google Maps yang konsisten dengan BerandaController
     */
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

        if ($lat !== null && $lng !== null && is_numeric($lat) && is_numeric($lng)) {
            return "https://www.google.com/maps/search/{$encodedName}/@{$lat},{$lng},17z";
        }

        return "https://www.google.com/maps/search/?api=1&query={$encodedName}";
    }
}