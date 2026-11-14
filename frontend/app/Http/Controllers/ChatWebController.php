<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChatWebController extends Controller
{
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
            Log::warning('Failed obtaining backend token for chat: ' . $e->getMessage());
        }

        return null;
    }

    public function index()
    {
        $token = $this->getBackendToken();
        $guestId = session('guest_chat_id');
        $chatHistory = [];

        try {
            $rawBackendUrl = config('services.medigo.url', env('MEDIGO_BE', 'http://127.0.0.1:8005'));
            $backendUrl = rtrim(str_replace('localhost', '127.0.0.1', $rawBackendUrl), '/');

            $http = Http::acceptJson()->timeout(4);
            if ($token) {
                $http->withToken($token);
            }

            $response = $http->get("{$backendUrl}/api/chat-ai/history", [
                'guest_id' => $guestId,
            ]);

            if ($response->successful()) {
                $chatHistory = $response->json('messages') ?? [];
                foreach ($chatHistory as &$chat) {
                    if (!empty($chat['faskes']) && is_array($chat['faskes'])) {
                        foreach ($chat['faskes'] as &$item) {
                            $thumb = $item['thumbnail_url'] ?? $item['foto'] ?? null;
                            $item['thumbnail_url'] = \App\Support\FaskesVisual::normalizeStorageUrl($thumb);
                        }
                        unset($item);
                    }
                }
                unset($chat);
            }
        } catch (\Exception $e) {
            Log::info('Failed fetching chat history: ' . $e->getMessage());
        }

        return view('users.chat', compact('chatHistory'));
    }

    public function sendChat(Request $request)
    {
        $token = $this->getBackendToken();
        $isLoggedIn = Auth::check() || !empty($token);

        // LOGIKA PEMBATASAN GUEST (6 KALI CHAT)
        // User yang sudah login di web tidak dikenakan kuota guest (mendapat jatah user login 100 prompt di backend)
        if (! $isLoggedIn) {
            $guestCount = session('guest_chat_count', 0);
            
            if ($guestCount >= 6) {
                return response()->json([
                    'status' => 'limit_reached',
                    'message' => 'Batas percobaan habis. Silakan login.'
                ], 403);
            }
            
            session(['guest_chat_count' => $guestCount + 1]);
        } else {
            // Jika sudah login, bersihkan counter percobaan tamu
            if (session()->has('guest_chat_count')) {
                session()->forget('guest_chat_count');
            }
        }

        // ID unik tamu agar backend bisa menyimpan, mengingat, & memigrasikan riwayat chat saat user login
        $guestId = session('guest_chat_id');
        if (! $guestId) {
            $guestId = (string) Str::uuid();
            session(['guest_chat_id' => $guestId]);
        }

        $rawBackendUrl = config('services.medigo.url', env('MEDIGO_BE', 'http://127.0.0.1:8005'));
        $backendUrl = rtrim(str_replace('localhost', '127.0.0.1', $rawBackendUrl), '/');
        
        // X-Service-Key menandai request ini dari server frontend Medigo, X-Client-IP adalah IP asli
        // pengunjung. Backend memakai IP ini untuk membatasi kuota tamu, supaya tidak bisa diakali
        // dengan mengganti cookie/guest_id.
        $http = Http::acceptJson()->withHeaders([
            'X-Service-Key' => (string) config('services.medigo.service_key'),
            'X-Client-IP' => (string) $request->ip(),
        ]);
        if ($token) {
            $http->withToken($token); // Sertakan token jika user sudah login
        }

        $response = $http->post("{$backendUrl}/api/chat-ai", [
            'message'  => $request->input('message'),
            'lat'      => $request->input('lat'),
            'lng'      => $request->input('lng'),
            'guest_id' => $guestId,
        ]);

        $responseData = $response->json();
        if (isset($responseData['faskes']) && is_array($responseData['faskes'])) {
            foreach ($responseData['faskes'] as &$item) {
                $thumb = $item['thumbnail_url'] ?? $item['foto'] ?? null;
                $item['thumbnail_url'] = \App\Support\FaskesVisual::normalizeStorageUrl($thumb);
            }
            unset($item);
        }

        return response()->json($responseData, $response->status());
    }
}