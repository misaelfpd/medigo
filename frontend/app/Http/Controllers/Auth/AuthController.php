<?php

namespace App\Http\Controllers\Auth;

use App\Auth\BackendSessionUserProvider;
use App\Auth\BackendUser;
use App\Http\Controllers\Controller;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Login, register, dan logout user.
 * Frontend tidak menyimpan data user sendiri: semua dicek dan disimpan di backend,
 * lalu token & data user dari backend disimpan di session.
 */
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

        // Simpan halaman terakhir agar setelah login user kembali ke sana
        $redirectUrl = $request->input('redirect');
        if ($this->isSafeReturnUrl($redirectUrl)) {
            session()->put('url.intended', $redirectUrl);
        } elseif (! session()->has('url.intended') && $this->isSafeReturnUrl(url()->previous())) {
            session()->put('url.intended', url()->previous());
        }

        return view('auth.login');
    }

    /**
     * Handle User Login (dicek ke backend: POST /api/login)
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

        try {
            $response = Http::acceptJson()->timeout(10)->post($this->backendUrl().'/api/login', [
                'email' => $credentials['email'],
                'password' => $credentials['password'],
            ]);
        } catch (\Exception $e) {
            Log::error('Backend login unreachable: '.$e->getMessage());

            return $this->loginFailed($request, 'Server Medigo sedang tidak bisa dihubungi. Coba lagi beberapa saat.');
        }

        if (! $response->successful()) {
            // Pesan dari backend: email/password salah atau akun diblokir admin
            $message = $response->json('errors.email.0')
                ?? 'Email atau kata sandi yang Anda masukkan tidak sesuai.';

            return $this->loginFailed($request, $message);
        }

        if (! $this->startSession($request, $response, $request->boolean('remember'))) {
            return $this->loginFailed($request, 'Gagal memproses data akun. Coba lagi.');
        }

        return $this->redirectAfterAuth($request, 'Selamat datang kembali, '.(Auth::user()->name ?? 'Pengguna').'!');
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
     * Handle User Registration (akun dibuat di backend: POST /api/register)
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:150'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        try {
            $response = Http::acceptJson()->timeout(10)->post($this->backendUrl().'/api/register', [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
            ]);
        } catch (\Exception $e) {
            Log::error('Backend register unreachable: '.$e->getMessage());

            return back()->withInput($request->only('name', 'email'))->withErrors([
                'email' => 'Server Medigo sedang tidak bisa dihubungi. Coba lagi beberapa saat.',
            ]);
        }

        if (! $response->successful()) {
            $errors = $response->json('errors') ?? [];
            $emailError = $errors['email'][0] ?? null;
            if ($emailError && str_contains(strtolower($emailError), 'taken')) {
                $emailError = 'Email ini sudah terdaftar. Silakan login atau gunakan email lain.';
            }

            return back()->withInput($request->only('name', 'email'))->withErrors([
                'email' => $emailError ?? ($errors['name'][0] ?? $errors['password'][0] ?? 'Pendaftaran gagal. Periksa kembali data Anda.'),
            ]);
        }

        if (! $this->startSession($request, $response)) {
            return redirect()->route('login')->with('success', 'Akun berhasil dibuat. Silakan login.');
        }

        return $this->redirectAfterAuth($request, 'Akun Anda berhasil dibuat! Selamat datang di MediGO.');
    }

    /**
     * Handle User Logout
     */
    public function logout(Request $request)
    {
        // Hapus token di backend juga
        $token = session('user_backend_token');
        if ($token) {
            try {
                Http::timeout(3)->withToken($token)->post($this->backendUrl().'/api/logout');
            } catch (\Exception $e) {
                // Silently ignore backend logout failure
            }
        }

        // Hapus data "Ingat saya" supaya cookie lama tidak bisa dipakai login lagi
        BackendSessionUserProvider::forgetRemembered();

        Auth::guard('web')->logout();
        $request->session()->forget(['user_backend_token', 'user_backend_data']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil keluar dari akun.');
    }

    /**
     * Simpan token & data user dari backend ke session, lalu tandai user sebagai login.
     */
    private function startSession(Request $request, Response $response, bool $remember = false): bool
    {
        $token = $response->json('access_token');
        $userData = $response->json('data');

        if (! $token || ! is_array($userData) || empty($userData['id_user'])) {
            return false;
        }

        $request->session()->regenerate();
        session([
            'user_backend_token' => $token,
            'user_backend_data' => $userData,
        ]);

        // "Ingat saya": data login disimpan sementara (lihat BackendSessionUserProvider::updateRememberToken)
        if ($remember) {
            session([BackendSessionUserProvider::REMEMBER_REQUESTED => true]);
        }

        Auth::login(BackendUser::fromBackend($userData), $remember);

        return true;
    }

    private function loginFailed(Request $request, string $message)
    {
        return back()->withInput($request->only('email', 'remember'))->withErrors([
            'email' => $message,
        ]);
    }

    private function redirectAfterAuth(Request $request, string $message)
    {
        // Kembali ke halaman terakhir sebelum login; jika tidak ada, ke beranda
        $redirectTo = $request->input('redirect');
        if ($this->isSafeReturnUrl($redirectTo)) {
            session()->forget('url.intended');

            return redirect($redirectTo)->with('success', $message);
        }

        return redirect()->intended(route('faskes.index'))->with('success', $message);
    }

    /**
     * Hanya URL di situs ini (bukan halaman login/daftar/logout) yang boleh jadi tujuan kembali.
     */
    private function isSafeReturnUrl(?string $url): bool
    {
        if (empty($url) || str_starts_with($url, '//')) {
            return false;
        }

        $path = str_starts_with($url, '/') ? $url : null;
        if ($path === null) {
            $host = parse_url($url, PHP_URL_HOST);
            if (! $host || strcasecmp($host, request()->getHost()) !== 0) {
                return false;
            }
            $path = parse_url($url, PHP_URL_PATH) ?: '/';
        }

        foreach (['/login', '/register', '/logout', '/admin'] as $blocked) {
            if (str_starts_with($path, $blocked)) {
                return false;
            }
        }

        return true;
    }

    private function backendUrl(): string
    {
        $rawBackendUrl = config('services.medigo.url', env('MEDIGO_BE', 'http://127.0.0.1:8005'));

        return rtrim(str_replace('localhost', '127.0.0.1', $rawBackendUrl), '/');
    }
}
