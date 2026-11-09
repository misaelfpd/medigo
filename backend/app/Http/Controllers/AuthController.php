<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // 1. Fungsi Register (Daftar Akun Baru)
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            // Sesuaikan validasi unique ke tabel 'user' dan kolom 'email_user'
            'email' => 'required|string|email|max:255|unique:user,email_user',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'nama_user' => $request->name,
            'email_user' => $request->email,
            'password_user' => Hash::make($request->password),
            'role_user' => 'user', // Default langsung jadi user biasa
        ]);

        $token = $user->createToken('medigo_token')->plainTextToken;

        return response()->json([
            'message' => 'Registrasi berhasil!',
            'data' => $user,
            'access_token' => $token,
        ], 201);
    }

    // 2. Fungsi Login
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Cari berdasarkan email_user
        $user = User::where('email_user', $request->email)->first();

        // Cek hash password_user
        if (!$user || !Hash::check($request->password, $user->password_user)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        $token = $user->createToken('medigo_token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil!',
            'data' => $user,
            'access_token' => $token,
        ]);
    }

    // 3. Fungsi Logout (Hapus Token)
    public function logout(Request $request)
    {
        // Hapus token yang lagi dipakai user saat ini
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil, token dihapus!'
        ]);
    }

    // 4. Token untuk server frontend (server-to-server, bukan untuk browser)
    // Frontend memanggil ini kalau session-nya belum punya token backend.
    // Wajib header X-Service-Key yang sama dengan MEDIGO_SERVICE_KEY.
    public function tokenByEmail(Request $request)
    {
        $expectedKey = (string) config('services.frontend.service_key');
        $providedKey = (string) $request->header('X-Service-Key', '');

        // Fail closed: tanpa key yang dikonfigurasi, rute ini mati total.
        if ($expectedKey === '' || ! hash_equals($expectedKey, $providedKey)) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email_user', $request->email)->first();

        // Jangan pernah bikin user baru di sini; daftar lewat /api/register.
        if (! $user) {
            return response()->json(['message' => 'User tidak ditemukan.'], 404);
        }

        // Akun admin wajib login pakai password lewat /api/login.
        if ($user->role_user !== 'user') {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $token = $user->createToken('medigo_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'user' => $user,
        ]);
    }
}
