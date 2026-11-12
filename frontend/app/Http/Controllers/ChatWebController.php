<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ChatWebController extends Controller
{
    public function index()
    {
        return view('users.chat');
    }

    public function sendChat(Request $request)
    {
        $token = session('user_backend_token');

        // LOGIKA PEMBATASAN GUEST (6 KALI CHAT)
        if (! $token) {
            $guestCount = session('guest_chat_count', 0);
            
            if ($guestCount >= 6) {
                return response()->json([
                    'status' => 'limit_reached',
                    'message' => 'Batas percobaan habis. Silakan login.'
                ], 403);
            }
            
            session(['guest_chat_count' => $guestCount + 1]);
        }

        // ID unik tamu agar backend bisa menyimpan & mengingat riwayat chat tamu
        $guestId = session('guest_chat_id');
        if (! $guestId) {
            $guestId = (string) Str::uuid();
            session(['guest_chat_id' => $guestId]);
        }

        $rawBackendUrl = config('services.medigo.url', env('MEDIGO_BE', 'http://127.0.0.1:8005'));
        $backendUrl = rtrim(str_replace('localhost', '127.0.0.1', $rawBackendUrl), '/');
        
        $http = Http::acceptJson();
        if ($token) {
            $http->withToken($token); // Sertakan token jika user sudah login
        }

        $response = $http->post("{$backendUrl}/api/chat-ai", [
            'message'  => $request->input('message'),
            'lat'      => $request->input('lat'),
            'lng'      => $request->input('lng'),
            'guest_id' => $guestId,
        ]);

        return response()->json($response->json(), $response->status());
    }
}