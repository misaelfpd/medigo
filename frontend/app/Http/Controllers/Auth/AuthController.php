<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * Show User Login Form
     */
    public function showLoginForm(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('profile');
        }

        // Store intended URL in session so redirect()->intended() works after login
        if ($request->has('redirect')) {
            $redirectUrl = $request->input('redirect');
            // Only store if it's a local URL (security: prevent open redirect)
            if (str_starts_with($redirectUrl, config('app.url')) || str_starts_with($redirectUrl, '/')) {
                session()->put('url.intended', $redirectUrl);
            }
        }

        return view('auth.login');
    }

    /**
     * Handle User Login
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $remember = $request->boolean('remember');

        // 1. Attempt login with local User table
        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            $user = Auth::user();

            // Try to sync with backend API and get sanctum token if available
            $this->syncBackendLogin($credentials['email'], $credentials['password']);

            // If a redirect was specified in the form, use it; otherwise use intended or profile
            $redirectTo = $request->input('redirect');
            if ($redirectTo && str_starts_with($redirectTo, config('app.url'))) {
                return redirect($redirectTo)->with('success', 'Selamat datang kembali, ' . ($user->name ?? 'Pengguna') . '!');
            }

            return redirect()->intended(route('profile'))->with('success', 'Selamat datang kembali, ' . ($user->name ?? 'Pengguna') . '!');
        }

        // 2. Fallback: Check if backend API has this user and sync locally
        $backendAuth = $this->attemptBackendLoginAndSync($credentials['email'], $credentials['password']);
        if ($backendAuth) {
            $request->session()->regenerate();

            $redirectTo = $request->input('redirect');
            if ($redirectTo && str_starts_with($redirectTo, config('app.url'))) {
                return redirect($redirectTo)->with('success', 'Selamat datang kembali, ' . (Auth::user()->name ?? 'Pengguna') . '!');
            }

            return redirect()->intended(route('profile'))->with('success', 'Selamat datang kembali, ' . (Auth::user()->name ?? 'Pengguna') . '!');
        }

        return back()->withInput($request->only('email', 'remember'))->withErrors([
            'email' => 'Email atau kata sandi yang Anda masukkan tidak sesuai.',
        ]);
    }

    /**
     * Show User Register Form
     */
    public function showRegisterForm()
    {
        if (Auth::check()) {
            return redirect()->route('profile');
        }

        return view('auth.register');
    }

    /**
     * Handle User Registration
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'terms' => ['accepted'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah terdaftar. Silakan login atau gunakan email lain.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
            'terms.accepted' => 'Anda harus menyetujui syarat & ketentuan MediGO.',
        ]);

        // 1. Create local user
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // 2. Try registering on backend API as well
        $this->syncBackendRegister($validated['name'], $validated['email'], $validated['password']);

        // 3. Auto login the new user
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('profile')->with('success', 'Akun Anda berhasil dibuat! Selamat datang di MediGO.');
    }

    /**
     * Handle User Logout
     */
    public function logout(Request $request)
    {
        // Try to call backend logout if token exists
        $token = session('user_backend_token');
        if ($token) {
            try {
                $rawBackendUrl = config('services.medigo.url', env('MEDIGO_BE', 'http://127.0.0.1:8005'));
                $backendUrl = rtrim(str_replace('localhost', '127.0.0.1', $rawBackendUrl), '/');
                Http::timeout(3)->withToken($token)->post("{$backendUrl}/api/logout");
            } catch (\Exception $e) {
                // Silently ignore backend logout failure
            }
        }

        Auth::guard('web')->logout();
        $request->session()->forget(['user_backend_token', 'user_backend_data']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil keluar dari akun.');
    }

    /**
     * Sync login with backend API
     */
    private function syncBackendLogin($email, $password)
    {
        try {
            $rawBackendUrl = config('services.medigo.url', env('MEDIGO_BE', 'http://127.0.0.1:8005'));
            $backendUrl = rtrim(str_replace('localhost', '127.0.0.1', $rawBackendUrl), '/');

            $response = Http::timeout(4)->post("{$backendUrl}/api/login", [
                'email' => $email,
                'password' => $password,
            ]);

            if ($response->successful()) {
                $body = $response->json();
                session([
                    'user_backend_token' => $body['access_token'] ?? null,
                    'user_backend_data' => $body['data'] ?? null,
                ]);
            }
        } catch (\Exception $e) {
            Log::info('Backend login sync bypassed: ' . $e->getMessage());
        }
    }

    /**
     * Sync registration with backend API
     */
    private function syncBackendRegister($name, $email, $password)
    {
        try {
            $rawBackendUrl = config('services.medigo.url', env('MEDIGO_BE', 'http://127.0.0.1:8005'));
            $backendUrl = rtrim(str_replace('localhost', '127.0.0.1', $rawBackendUrl), '/');

            $response = Http::timeout(4)->post("{$backendUrl}/api/register", [
                'name' => $name,
                'email' => $email,
                'password' => $password,
            ]);

            if ($response->successful()) {
                $body = $response->json();
                session([
                    'user_backend_token' => $body['access_token'] ?? null,
                    'user_backend_data' => $body['data'] ?? null,
                ]);
            }
        } catch (\Exception $e) {
            Log::info('Backend register sync bypassed: ' . $e->getMessage());
        }
    }

    /**
     * Attempt backend login and synchronize local user if found
     */
    private function attemptBackendLoginAndSync($email, $password)
    {
        try {
            $rawBackendUrl = config('services.medigo.url', env('MEDIGO_BE', 'http://127.0.0.1:8005'));
            $backendUrl = rtrim(str_replace('localhost', '127.0.0.1', $rawBackendUrl), '/');

            $response = Http::timeout(4)->post("{$backendUrl}/api/login", [
                'email' => $email,
                'password' => $password,
            ]);

            if ($response->successful()) {
                $body = $response->json();
                $userData = $body['data'] ?? [];

                // Create or update local user record
                $user = User::updateOrCreate(
                    ['email' => $email],
                    [
                        'name' => $userData['name'] ?? explode('@', $email)[0],
                        'password' => Hash::make($password),
                    ]
                );

                Auth::login($user);
                session([
                    'user_backend_token' => $body['access_token'] ?? null,
                    'user_backend_data' => $userData,
                ]);

                return true;
            }
        } catch (\Exception $e) {
            Log::warning('Backend login fallback failed: ' . $e->getMessage());
        }

        return false;
    }
}
