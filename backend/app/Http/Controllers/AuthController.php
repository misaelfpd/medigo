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

        if ($user->role_user === 'blocked' || $user->role_user === 'banned') {
            throw ValidationException::withMessages([
                'email' => ['Akun Anda telah dinonaktifkan atau diblokir oleh administrator karena pelanggaran pedoman komunitas.'],
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

    // 5. Ambil data profil user yang sedang login
    public function profile(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil mengambil data profil user',
            'data' => $user,
        ]);
    }

    // 6. Fungsi Update Profile (Nama, Email, dan/atau Password)
    public function updateProfile(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $request->validate([
            'name' => 'sometimes|nullable|string|max:150',
            'nama_user' => 'sometimes|nullable|string|max:150',
            'email' => 'sometimes|nullable|email|max:150|unique:user,email_user,'.$user->id_user.',id_user',
            'email_user' => 'sometimes|nullable|email|max:150|unique:user,email_user,'.$user->id_user.',id_user',
            'no_telp' => 'sometimes|nullable|string|max:50',
            'phone' => 'sometimes|nullable|string|max:50',
            'tanggal_lahir' => 'sometimes|nullable|date',
            'birth_date' => 'sometimes|nullable|date',
            'current_password' => 'nullable|string',
            'password_lama' => 'nullable|string',
            'new_password' => 'nullable|string|min:8',
            'password_baru' => 'nullable|string|min:8',
            'password' => 'nullable|string|min:8',
        ], [
            'email.unique' => 'Email sudah digunakan oleh pengguna lain.',
            'email_user.unique' => 'Email sudah digunakan oleh pengguna lain.',
            'new_password.min' => 'Kata sandi baru minimal 8 karakter.',
            'password_baru.min' => 'Kata sandi baru minimal 8 karakter.',
            'password.min' => 'Kata sandi baru minimal 8 karakter.',
        ]);

        // 1. Update Nama jika ada
        $newName = $request->input('nama_user') ?? $request->input('name');
        if (! empty($newName)) {
            $user->nama_user = trim($newName);
        }

        // 2. Update Email jika ada dan berbeda
        $newEmail = $request->input('email_user') ?? $request->input('email');
        if (! empty($newEmail) && $newEmail !== $user->email_user) {
            $user->email_user = trim($newEmail);
        }

        // 3. Update Nomor Telepon
        $newPhone = $request->input('no_telp') ?? $request->input('phone');
        if ($newPhone !== null) {
            $user->no_telp = trim($newPhone);
        }

        // 4. Update Tanggal Lahir
        $newBirthDate = $request->input('tanggal_lahir') ?? $request->input('birth_date');
        if ($newBirthDate !== null) {
            $user->tanggal_lahir = ! empty($newBirthDate) ? $newBirthDate : null;
        }

        // 5. Update Avatar jika ada
        $newAvatar = $request->input('avatar');
        if (! empty($newAvatar)) {
            $user->avatar = trim($newAvatar);
        }

        // 6. Update Password jika ada
        $newPassword = $request->input('new_password') ?? $request->input('password_baru') ?? $request->input('password');
        if (! empty($newPassword)) {
            $currentPassword = $request->input('current_password') ?? $request->input('password_lama');

            if (empty($currentPassword)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Kata sandi saat ini wajib diisi untuk mengubah kata sandi.',
                    'errors' => [
                        'current_password' => ['Kata sandi saat ini wajib diisi untuk mengubah kata sandi.'],
                    ],
                ], 422);
            }

            if (! Hash::check($currentPassword, $user->password_user)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Kata sandi saat ini tidak sesuai.',
                    'errors' => [
                        'current_password' => ['Kata sandi saat ini tidak sesuai.'],
                    ],
                ], 422);
            }

            $user->password_user = Hash::make($newPassword);
        }

        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Profil berhasil diperbarui!',
            'data' => $user,
        ]);
    }
}
