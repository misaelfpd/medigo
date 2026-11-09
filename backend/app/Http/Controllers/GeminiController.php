<?php

namespace App\Http\Controllers;

use App\Models\GeminiChat;
use App\Models\Faskes; // Pastikan model Faskes lu dipanggil
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;

class GeminiController extends Controller
{
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string'
        ]);

        $user = Auth::user();
        $userMessage = $request->message;
        $apiKey = env('GEMINI_API_KEY');

        // 1. Ambil Ingatan 24 Jam Terakhir dari Database
        $history = GeminiChat::where('id_user', $user->id_user)
            ->where('created_at', '>=', now()->subDay())
            ->orderBy('created_at', 'asc')
            ->get();

        // 2. Format Ingatan ke standar Google Gemini API
        $contents = [];
        foreach ($history as $chat) {
            $contents[] = [
                "role" => $chat->role,
                "parts" => [["text" => $chat->message]]
            ];
        }

        // Tambahkan pesan user yang baru
        $contents[] = [
            "role" => "user",
            "parts" => [["text" => $userMessage]]
        ];

        // 3. Setup Payload (Persona & Function Calling)
        $payload = [
            "system_instruction" => [
                "parts" => [
                    "text" => "Kamu adalah Asisten Medigo, AI ahli kesehatan yang ramah. Tugasmu membantu user mencari fasilitas kesehatan, memberikan saran medis awal, dan merangkum diskusi. Gunakan bahasa Indonesia yang santai dan empati. Jika user mencari klinik/faskes, SELALU gunakan fungsi cari_faskes."
                ]
            ],
            "contents" => $contents,
            "tools" => [
                [
                    "function_declarations" => [
                        [
                            "name" => "cari_faskes",
                            "description" => "Mencari data fasilitas kesehatan (faskes) di database berdasarkan kata kunci pencarian",
                            "parameters" => [
                                "type" => "OBJECT",
                                "properties" => [
                                    "keyword" => [
                                        "type" => "STRING",
                                        "description" => "Kata kunci pencarian, misalnya: 'gigi', 'umum', 'jakarta'"
                                    ]
                                ],
                                "required" => ["keyword"]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        // 4. Tembak ke API Gemini
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}";

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($url, $payload);

        $geminiData = $response->json();

        // 5. Cek apakah Gemini mau pakai Function Calling
        if (isset($geminiData['candidates'][0]['content']['parts'][0]['functionCall'])) {
            $functionCall = $geminiData['candidates'][0]['content']['parts'][0]['functionCall'];

            if ($functionCall['name'] === 'cari_faskes') {
                $keyword = $functionCall['args']['keyword'];

                // Cari ke Database lu
                $faskesResult = Faskes::where('nama_faskes', 'like', "%{$keyword}%")->limit(3)->get();

                $hasilPencarian = $faskesResult->isEmpty()
                    ? "Maaf, faskes dengan kata kunci {$keyword} tidak ditemukan."
                    : "Berikut data faskes yang ditemukan: " . json_encode($faskesResult);

                // Kasih tau Gemini hasil dari database, dan suruh dia rangkum buat user
                // (Ini adalah siklus ping-pong-nya)
                $contents[] = [
                    "role" => "model",
                    "parts" => [["functionCall" => $functionCall]]
                ];
                $contents[] = [
                    "role" => "function",
                    "parts" => [["functionResponse" => ["name" => "cari_faskes", "response" => ["result" => $hasilPencarian]]]]
                ];

                $payload['contents'] = $contents;
                $finalResponse = Http::post($url, $payload)->json();
                $aiReply = $finalResponse['candidates'][0]['content']['parts'][0]['text'];
            }
        } else {
            // Kalau nggak pakai function (misal cuma nanya gejala pusing)
            $aiReply = $geminiData['candidates'][0]['content']['parts'][0]['text'] ?? 'Maaf, sistem AI sedang gangguan.';
        }

        // 6. Simpan percakapan ini ke Database sebagai ingatan
        GeminiChat::create([
            'id_user' => $user->id_user,
            'role' => 'user',
            'message' => $userMessage
        ]);

        GeminiChat::create([
            'id_user' => $user->id_user,
            'role' => 'model',
            'message' => $aiReply
        ]);

        return response()->json([
            'message' => 'Success',
            'data' => $aiReply
        ]);
    }
}