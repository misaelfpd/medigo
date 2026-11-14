<?php

namespace App\Auth;

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Carbon;

/**
 * User yang sedang login di frontend. Datanya berasal dari backend (tabel `user` di MySQL)
 * dan disimpan di session (`user_backend_data`), bukan di database frontend.
 *
 * Properti yang tersedia (sama seperti model User lama, supaya view tidak perlu diubah):
 * id, name, email, phone, birth_date, avatar, avatar_url, role,
 * plus nama asli dari backend: id_user, nama_user, email_user, no_telp, tanggal_lahir, role_user.
 */
class BackendUser extends GenericUser
{
    public static function fromBackend(array $data): self
    {
        $birthDate = $data['tanggal_lahir'] ?? null;

        return new self([
            'id' => (int) ($data['id_user'] ?? 0),
            'id_user' => (int) ($data['id_user'] ?? 0),
            'name' => $data['nama_user'] ?? '',
            'nama_user' => $data['nama_user'] ?? '',
            'email' => $data['email_user'] ?? '',
            'email_user' => $data['email_user'] ?? '',
            'phone' => $data['no_telp'] ?? null,
            'no_telp' => $data['no_telp'] ?? null,
            'birth_date' => $birthDate ? Carbon::parse($birthDate) : null,
            'tanggal_lahir' => $birthDate,
            'avatar' => $data['avatar'] ?? null,
            'avatar_url' => self::avatarUrl($data['avatar'] ?? null),
            'role' => $data['role_user'] ?? 'user',
            'role_user' => $data['role_user'] ?? 'user',
            'password' => '',
            'remember_token' => null,
        ]);
    }

    public function isAdmin(): bool
    {
        return ($this->attributes['role'] ?? null) === 'admin';
    }

    /**
     * Foto profil disimpan di folder public frontend (uploads/avatars), path-nya disimpan di backend.
     */
    private static function avatarUrl(?string $avatar): ?string
    {
        if (! empty($avatar) && file_exists(public_path($avatar))) {
            return asset($avatar);
        }

        // Tanpa foto: tampilan memakai huruf depan nama (komponen <x-avatar>)
        return null;
    }
}
