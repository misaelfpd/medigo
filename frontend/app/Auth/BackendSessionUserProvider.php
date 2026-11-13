<?php

namespace App\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Facades\Cache;

/**
 * Sumber data user untuk Auth::user(), Auth::check(), @auth, dst.
 * Tidak memakai database frontend: user diambil dari data backend yang disimpan di session
 * saat login (session `user_backend_data`). Login/register/cek password dilakukan oleh backend.
 *
 * "Ingat saya": token backend & data user disimpan di cache selama auth.guards.web.remember menit
 * (default 3 hari), dengan kunci dari token di cookie remember. Kalau session sudah habis,
 * login dipulihkan dari cache itu.
 */
class BackendSessionUserProvider implements UserProvider
{
    /** Penanda di session bahwa user mencentang "Ingat saya" saat login. */
    public const REMEMBER_REQUESTED = 'remember_login_requested';

    /** Kunci cache data "Ingat saya" milik session ini. */
    private const REMEMBER_CACHE_KEY = 'remember_cache_key';

    public function retrieveById($identifier): ?Authenticatable
    {
        $data = session('user_backend_data');

        if (! is_array($data) || (int) ($data['id_user'] ?? 0) !== (int) $identifier) {
            return null;
        }

        return BackendUser::fromBackend($data);
    }

    /**
     * Session sudah habis tapi cookie "Ingat saya" masih ada: pulihkan login dari cache.
     */
    public function retrieveByToken($identifier, #[\SensitiveParameter] $token): ?Authenticatable
    {
        if (empty($token)) {
            return null;
        }

        $key = self::cacheKey($token);
        $entry = Cache::get($key);

        if (! is_array($entry) || (int) ($entry['user']['id_user'] ?? 0) !== (int) $identifier || empty($entry['backend_token'])) {
            return null;
        }

        session([
            'user_backend_token' => $entry['backend_token'],
            'user_backend_data' => $entry['user'],
            self::REMEMBER_CACHE_KEY => $key,
        ]);

        $user = BackendUser::fromBackend($entry['user']);
        $user->setRememberToken($token);

        return $user;
    }

    /**
     * Dipanggil guard saat login dengan "Ingat saya" (dan saat logout, untuk mengganti token).
     * Data hanya disimpan kalau login memang meminta "Ingat saya".
     */
    public function updateRememberToken(Authenticatable $user, #[\SensitiveParameter] $token): void
    {
        self::forgetRemembered();

        if (! session()->pull(self::REMEMBER_REQUESTED) || ! session('user_backend_token')) {
            return;
        }

        $key = self::cacheKey($token);
        Cache::put($key, [
            'backend_token' => session('user_backend_token'),
            'user' => session('user_backend_data'),
        ], now()->addMinutes(self::rememberMinutes()));

        session([self::REMEMBER_CACHE_KEY => $key]);
    }

    // Cek email & password dilakukan backend lewat POST /api/login, bukan di frontend.
    public function retrieveByCredentials(#[\SensitiveParameter] array $credentials): ?Authenticatable
    {
        return null;
    }

    public function validateCredentials(Authenticatable $user, #[\SensitiveParameter] array $credentials): bool
    {
        return false;
    }

    public function rehashPasswordIfRequired(Authenticatable $user, #[\SensitiveParameter] array $credentials, bool $force = false): void {}

    /**
     * Setelah profil diubah, perbarui juga data "Ingat saya" supaya tidak kembali ke data lama.
     */
    public static function refreshRemembered(): void
    {
        $key = session(self::REMEMBER_CACHE_KEY);
        $entry = $key ? Cache::get($key) : null;

        if (is_array($entry)) {
            $entry['user'] = session('user_backend_data');
            Cache::put($key, $entry, now()->addMinutes(self::rememberMinutes()));
        }
    }

    /**
     * Hapus data "Ingat saya" milik session ini (dipanggil saat logout).
     */
    public static function forgetRemembered(): void
    {
        $key = session()->pull(self::REMEMBER_CACHE_KEY);
        if ($key) {
            Cache::forget($key);
        }
    }

    private static function cacheKey(string $token): string
    {
        return 'medigo_remember:'.hash('sha256', $token);
    }

    private static function rememberMinutes(): int
    {
        return max(1, (int) config('auth.guards.web.remember', 4320));
    }
}
