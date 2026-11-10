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
    private const SYSTEM_PROMPT = 'Kamu adalah Asisten Medigo, AI ahli kesehatan yang ramah. Tugasmu membantu user mencari fasilitas kesehatan, memberikan saran medis awal, dan merangkum diskusi. Gunakan bahasa Indonesia yang santai dan empati. Jika user mencari klinik/faskes, SELALU gunakan fungsi cari_faskes.';

    /** Chat turns from the last 24 hours sent back as memory. */
    private const HISTORY_LIMIT = 20;

    /** Stops a model that keeps calling tools from looping forever. */
    private const MAX_TOOL_ROUNDS = 3;

    private const UNAVAILABLE = 'Maaf, Asisten Medigo sedang tidak tersedia. Coba lagi beberapa saat lagi ya.';

    public function chat(Request $request, GeminiClient $gemini): JsonResponse {
    //     dd([
    //     'is_configured' => $gemini->isConfigured(),
    //     'api_key_env' => env('GEMINI_API_KEY'),
    //     'request_data' => $request->all(),
    //     'user' => $request->user()
    // ]);
        
        $request->validate([
            'message' => 'required|string|max:2000',
        ]);
        
        if (! $gemini->isConfigured()) {
            return response()->json(['message' => self::UNAVAILABLE], 503);
        }

        $userMessage = $request->input('message');

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
            $aiReply = $this->converse($gemini, $contents);
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
    private function converse(GeminiClient $gemini, array $contents): string
    {
        $model = null; // Chosen on the first call, then kept for the follow-ups.

        for ($round = 0; $round <= self::MAX_TOOL_ROUNDS; $round++) {
            [$model, $data] = $gemini->generate($this->payload($contents), $model);

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
                    'response' => $this->runTool($part['functionCall']),
                ])], $calls),
            ];
        }

        throw new RuntimeException('Gemini kept calling tools without answering');
    }

    private function runTool(array $call): array
    {
        if ($call['name'] !== 'cari_faskes') {
            return ['error' => "Fungsi {$call['name']} tidak tersedia."];
        }

        $args = $call['args'] ?? [];
        $results = $this->searchFaskesAdvanced($args);

        return $results->isEmpty()
            ? ['message' => "Tidak ada faskes yang cocok dengan kriteria tersebut."]
            : ['faskes' => $results->all()];
    }

    /**
     * Every word must match the name, type, city or district, case-insensitively
     * ("gigi bogor" finds dental clinics in Bogor).
     */
    private function searchFaskes(string $keyword)
    {
        $query = Faskes::query()->select([
            'id_faskes', 'nama_faskes', 'jenis_faskes', 'alamat_faskes',
            'kecamatan', 'kota_kabupaten', 'provinsi', 'link_maps', '_support_bpjs',
        ]);

        foreach (array_slice(preg_split('/\s+/', mb_strtolower($keyword)), 0, 5) as $word) {
            $pattern = '%'.addcslashes($word, '\\%_').'%';
            $query->where(function ($q) use ($pattern) {
                foreach (['nama_faskes', 'jenis_faskes', 'kota_kabupaten', 'kecamatan'] as $column) {
                    $q->orWhereRaw("LOWER({$column}) LIKE ? ESCAPE '\\'", [$pattern]);
                }
            });
        }

        return $query->limit(5)->get();
    }

    private function payload(array $contents): array
    {
        return [
            'system_instruction' => ['parts' => [['text' => self::SYSTEM_PROMPT]]],
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
                                'description' => "Kata kunci umum atau nama faskes, misalnya: 'Primaya', 'RSUD', 'Klinik'."
                            ],
                            'kota_kabupaten' => [
                                'type' => 'STRING',
                                'description' => "Filter wilayah kota atau kabupaten, misalnya: 'Kota Bogor' atau 'Kab. Bogor'."
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
            $haversine = "(6371 * acos(cos(radians({$lat})) * cos(radians(faskes.latittude)) * cos(radians(faskes.longitude) - radians({$lng})) + sin(radians({$lat})) * sin(radians(faskes.latittude))))";
            
            $query->selectRaw("{$haversine} AS distance_km")
                  ->whereNotNull('faskes.latittude')
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
