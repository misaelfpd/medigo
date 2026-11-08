<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdminAuthController extends Controller
{
    public function showLoginForm()
    {
        if (session('admin_logged_in')) {
            return redirect()->route('admin.dashboard');
        }
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        $email = $request->input('email');
        $password = $request->input('password');

        $rawBackendUrl = config('services.medigo.url', env('MEDIGO_BE', 'http://127.0.0.1:8005'));
        $backendUrl = rtrim(str_replace('localhost', '127.0.0.1', $rawBackendUrl), '/');

        // Try authenticating with backend API
        try {
            $response = Http::timeout(5)->post("{$backendUrl}/api/login", [
                'email' => $email,
                'password' => $password,
            ]);

            if ($response->successful()) {
                $body = $response->json();
                $user = $body['data'] ?? [];
                $token = $body['access_token'] ?? null;

                session([
                    'admin_logged_in' => true,
                    'admin_user' => $user,
                    'admin_token' => $token,
                ]);

                return redirect()->route('admin.dashboard')->with('success', 'Selamat datang kembali di Dashboard Admin MediGO!');
            }
        } catch (\Exception $e) {
            Log::warning('Backend admin login failed, checking local credentials: ' . $e->getMessage());
        }

        // Default / demo admin login fallback
        if ($email === 'admin@medigo.id' && $password === 'password') {
            session([
                'admin_logged_in' => true,
                'admin_user' => [
                    'id' => 1,
                    'name' => 'Admin MediGO',
                    'email' => 'admin@medigo.id',
                    'role' => 'admin',
                ],
                'admin_token' => 'mock_admin_token_123',
            ]);

            return redirect()->route('admin.dashboard')->with('success', 'Selamat datang kembali di Dashboard Admin MediGO!');
        }

        return back()->withInput($request->only('email'))->withErrors([
            'email' => 'Email atau kata sandi tidak valid. Silakan coba lagi.',
        ]);
    }

    public function logout(Request $request)
    {
        $request->session()->forget(['admin_logged_in', 'admin_user', 'admin_token']);
        $request->session()->flush();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('success', 'Anda telah berhasil keluar dari akun administrator.');
    }
}
