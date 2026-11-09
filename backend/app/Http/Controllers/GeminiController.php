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

    public function chat(Request $request, GeminiClient $gemini): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        if (! $gemini->isConfigured()) {
            return response()->json(['message' => self::UNAVAILABLE], 503);
        }

        $user = $request->user();
        $userMessage = $request->input('message');

        $contents = GeminiChat::where('id_user', $user->id_user)
            ->where('created_at', '>=', now()->subDay())
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::HISTORY_LIMIT)
            ->get()
            ->reverse()
            ->map(fn (GeminiChat $chat) => ['role' => $chat->role, 'parts' => [['text' => $chat->message]]])
            ->values()
            ->all();

        $contents[] = ['role' => 'user', 'parts' => [['text' => $userMessage]]];

        try {
            $aiReply = $this->converse($gemini, $contents);
        } catch (RuntimeException $e) {
            Log::error('Gemini chat failed', ['user' => $user->id_user, 'error' => $e->getMessage()]);

            return response()->json(['message' => self::UNAVAILABLE], 502);
        }

        // Only a real answer is kept as memory; failures above are never saved.
        DB::transaction(function () use ($user, $userMessage, $aiReply) {
            GeminiChat::create(['id_user' => $user->id_user, 'role' => 'user', 'message' => $userMessage]);
            GeminiChat::create(['id_user' => $user->id_user, 'role' => 'model', 'message' => $aiReply]);
        });

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

        $keyword = trim((string) ($call['args']['keyword'] ?? ''));
        $results = $keyword === '' ? collect() : $this->searchFaskes($keyword);

        return $results->isEmpty()
            ? ['message' => "Faskes dengan kata kunci \"{$keyword}\" tidak ditemukan."]
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
                    'description' => 'Mencari data fasilitas kesehatan (faskes) di database berdasarkan kata kunci pencarian',
                    'parameters' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'keyword' => [
                                'type' => 'STRING',
                                'description' => "Kata kunci pencarian, misalnya: 'gigi', 'umum', 'jakarta'",
                            ],
                        ],
                        'required' => ['keyword'],
                    ],
                ]],
            ]],
        ];
    }
}
