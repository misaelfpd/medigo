<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    /**
     * Display the User Profile page or Guest Call-to-Action
     */
    public function index(Request $request)
    {
        $isLoggedIn = Auth::check();
        $user = Auth::user();

        $stats = [
            'reviews_count' => 0,
            'discussions_count' => 0,
            'bookmarks_count' => 0,
        ];

        $bookmarks = [];
        $myDiscussions = [];
        $myForumComments = []; // Tambahan variabel untuk komentar forum
        $userReviews = [];
        $hasMoreReviews = false;
        $totalReviews = 0;

        if ($isLoggedIn && $user) {
            $token = $this->getBackendToken();
            $rawBackendUrl = config('services.medigo.url', env('MEDIGO_BE', 'http://127.0.0.1:8005'));
            $backendUrl = rtrim(str_replace('localhost', '127.0.0.1', $rawBackendUrl), '/');

            if ($token) {
                try {
                    // Fetch user reviews (Faskes)
                    $revResponse = Http::timeout(3)->withToken($token)->get("{$backendUrl}/api/user/reviews", [
                        'limit' => 3,
                        'page' => 1
                    ]);
                    if ($revResponse->successful()) {
                        $revData = $revResponse->json('data') ?? [];
                        $userReviews = $revData['reviews'] ?? [];
                        $totalReviews = (int)($revData['total_reviews'] ?? count($userReviews));
                        $hasMoreReviews = (bool)($revData['has_more'] ?? false);
                        $stats['reviews_count'] = $totalReviews;
                    }

                    // Fetch user bookmarks
                    $bmResponse = Http::timeout(2)->withToken($token)->get("{$backendUrl}/api/bookmarks");
                    if ($bmResponse->successful()) {
                        $bmData = $bmResponse->json('data') ?? [];
                        if (!empty($bmData)) {
                            $bookmarks = is_array($bmData) ? $bmData : [];
                            $stats['bookmarks_count'] = count($bookmarks);
                        }
                    }

                    // Fetch user discussions (API Baru)
                    $discResponse = Http::timeout(3)->withToken($token)->get("{$backendUrl}/api/user/discussions");
                    if ($discResponse->successful()) {
                        $rawDisc = $discResponse->json('data') ?? [];
                        $myDiscussions = collect($rawDisc)->map(function ($item) {
                            $item = (array) $item;
                            $id = $item['id_diskusi'] ?? $item['id'] ?? null;
                            $title = $item['judul_diskusi'] ?? $item['title'] ?? '';
                            $body = $item['isi_diskusi'] ?? $item['body'] ?? ($item['content'] ?? '');
                            $category = $item['kategori'] ?? $item['category'] ?? 'Umum';
                            $img = $item['gambar_diskusi'] ?? $item['image_path'] ?? null;
                            return array_merge($item, [
                                'id' => $id,
                                'id_diskusi' => $id,
                                'title' => $title,
                                'judul_diskusi' => $title,
                                'body' => $body,
                                'isi_diskusi' => $body,
                                'category' => $category,
                                'kategori' => $category,
                                'image_path' => $img,
                                'gambar_diskusi' => $img,
                            ]);
                        })->all();
                        $stats['discussions_count'] = count($myDiscussions);
                    }

                    // Fetch user forum comments (API Baru)
                    $forumCommResponse = Http::timeout(3)->withToken($token)->get("{$backendUrl}/api/user/comments");
                    if ($forumCommResponse->successful()) {
                        $rawComm = $forumCommResponse->json('data') ?? [];
                        $myForumComments = collect($rawComm)->map(function ($item) {
                            $item = (array) $item;
                            $id = $item['id_tanggapan'] ?? $item['id'] ?? null;
                            $discId = $item['id_diskusi'] ?? $item['discussion_id'] ?? null;
                            $body = $item['isi_tanggapan'] ?? $item['body'] ?? ($item['content'] ?? '');
                            $discTitle = $item['discussion_title'] ?? $item['judul_diskusi'] ?? 'Diskusi Terkait';
                            return array_merge($item, [
                                'id' => $id,
                                'id_tanggapan' => $id,
                                'discussion_id' => $discId,
                                'id_diskusi' => $discId,
                                'body' => $body,
                                'isi_tanggapan' => $body,
                                'discussion_title' => $discTitle,
                            ]);
                        })->all();
                    }
                } catch (\Exception $e) {
                    Log::info('Profile data fetch from backend skipped: ' . $e->getMessage());
                }
            }
        }

        $savedHospitals = [];
        $activities = [];

        return view('users.profile', compact(
            'isLoggedIn', 
            'user', 
            'stats', 
            'bookmarks', 
            'myDiscussions', 
            'myForumComments',
            'savedHospitals', 
            'activities',
            'userReviews',
            'hasMoreReviews',
            'totalReviews'
        ));
    }

    /**
     * AJAX endpoint to load more user reviews for profile page
     */
    public function getComments(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $token = $this->getBackendToken();
        $rawBackendUrl = config('services.medigo.url', env('MEDIGO_BE', 'http://127.0.0.1:8005'));
        $backendUrl = rtrim(str_replace('localhost', '127.0.0.1', $rawBackendUrl), '/');

        $page = (int)$request->input('page', 1);
        $limit = (int)$request->input('limit', 3);

        if ($token) {
            try {
                $response = Http::timeout(3)->withToken($token)->get("{$backendUrl}/api/user/reviews", [
                    'page' => $page,
                    'limit' => $limit
                ]);

                if ($response->successful()) {
                    return response()->json($response->json());
                }
            } catch (\Exception $e) {
                Log::error('Profile getComments error: ' . $e->getMessage());
            }
        }

        // Data ulasan hanya ada di backend (frontend tidak punya tabel komentar)
        return response()->json([
            'status' => 'error',
            'message' => 'Gagal memuat ulasan. Coba muat ulang halaman.',
        ], 503);
    }

    /**
     * Get or refresh user Sanctum token from Backend API
     */
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
            Log::warning('Failed obtaining backend token in ProfileController: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Show the dedicated Edit Profile page
     */
    public function edit()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        return view('users.profile-edit', compact('user'));
    }

    /**
     * Update user profile information.
     * Data disimpan di backend (PUT /api/user). Frontend hanya menyimpan file foto profil
     * di public/uploads/avatars dan mengirim path-nya ke backend.
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'birth_date' => ['nullable', 'date'],
            'current_password' => ['nullable', 'required_with:new_password', 'string'],
            'new_password' => ['nullable', 'string', 'min:8'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ], [
            'name.required' => 'Nama lengkap tidak boleh kosong.',
            'email.required' => 'Alamat email tidak boleh kosong.',
            'email.email' => 'Format email tidak valid.',
            'current_password.required_with' => 'Kata sandi saat ini wajib diisi untuk mengubah kata sandi.',
            'new_password.min' => 'Kata sandi baru minimal 8 karakter.',
            'avatar.image' => 'File harus berupa gambar.',
            'avatar.mimes' => 'Format foto profil harus JPG, JPEG, atau PNG.',
            'avatar.max' => 'Ukuran foto profil tidak boleh melebihi 2MB.',
        ]);

        // 1. Simpan foto profil baru (hasil crop base64 atau file upload) ke public/uploads/avatars
        $newAvatarPath = null;
        $avatarCropped = $request->input('avatar_cropped');

        if (!empty($avatarCropped)) {
            // Validasi format data URL (hanya jpeg, jpg, png)
            if (!preg_match('/^data:image\/(jpeg|jpg|png);base64,/', $avatarCropped, $matches)) {
                return back()->withErrors(['avatar' => 'Format foto tidak valid. Gunakan format JPG, JPEG, atau PNG.'])->withInput();
            }

            $decodedData = base64_decode(substr($avatarCropped, strpos($avatarCropped, ',') + 1), true);
            if ($decodedData === false || (function_exists('imagecreatefromstring') && @imagecreatefromstring($decodedData) === false)) {
                return back()->withErrors(['avatar' => 'Gagal memproses gambar foto profil.'])->withInput();
            }

            // Validasi ukuran maksimal 2MB (2,097,152 bytes)
            if (strlen($decodedData) > 2 * 1024 * 1024) {
                return back()->withErrors(['avatar' => 'Ukuran foto profil tidak boleh melebihi 2MB.'])->withInput();
            }

            $ext = (strtolower($matches[1]) === 'png') ? 'png' : 'jpg';
            $filename = 'avatar_' . $user->id . '_' . time() . '.' . $ext;
            $this->ensureAvatarDir();
            file_put_contents(public_path('uploads/avatars') . DIRECTORY_SEPARATOR . $filename, $decodedData);
            $newAvatarPath = 'uploads/avatars/' . $filename;
        } elseif ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $ext = strtolower($file->extension() ?: 'jpg');
            $filename = 'avatar_' . $user->id . '_' . time() . '.' . $ext;
            $this->ensureAvatarDir();
            $file->move(public_path('uploads/avatars'), $filename);
            $newAvatarPath = 'uploads/avatars/' . $filename;
        }

        // 2. Cek apakah ada perubahan
        $trimmedName = trim($validated['name']);
        $trimmedEmail = trim($validated['email']);
        $trimmedPhone = !empty($validated['phone']) ? trim($validated['phone']) : null;
        $formattedBirthDate = !empty($validated['birth_date']) ? \Carbon\Carbon::parse($validated['birth_date'])->format('Y-m-d') : null;
        $currentBirthDate = $user->birth_date ? \Carbon\Carbon::parse($user->birth_date)->format('Y-m-d') : null;
        $newPassword = $validated['new_password'] ?? null;
        $passwordChanged = !empty($newPassword);

        $hasChanges = $newAvatarPath !== null
            || $trimmedName !== $user->name
            || strtolower($trimmedEmail) !== strtolower($user->email)
            || $trimmedPhone !== $user->phone
            || $formattedBirthDate !== $currentBirthDate
            || $passwordChanged;

        if (!$hasChanges) {
            return redirect()->route('profile');
        }

        // 3. Simpan ke backend (satu-satunya tempat data user disimpan)
        $token = $this->getBackendToken();
        if (!$token) {
            $this->deleteAvatarFile($newAvatarPath);
            return back()->withErrors(['email' => 'Sesi login Anda sudah habis. Silakan logout lalu login kembali.'])->withInput();
        }

        $payload = [
            'name' => $trimmedName,
            'email' => $trimmedEmail,
            'phone' => $trimmedPhone,
            'birth_date' => $formattedBirthDate,
        ];
        if ($passwordChanged) {
            // Kata sandi lama dicek oleh backend
            $payload['current_password'] = $validated['current_password'];
            $payload['new_password'] = $newPassword;
        }
        if ($newAvatarPath !== null) {
            $payload['avatar'] = $newAvatarPath;
        }

        try {
            $rawBackendUrl = config('services.medigo.url', env('MEDIGO_BE', 'http://127.0.0.1:8005'));
            $backendUrl = rtrim(str_replace('localhost', '127.0.0.1', $rawBackendUrl), '/');

            $beResponse = Http::acceptJson()->timeout(8)
                ->withToken($token)
                ->put("{$backendUrl}/api/user", $payload);
        } catch (\Exception $e) {
            Log::warning('Backend profile update error: ' . $e->getMessage());
            $this->deleteAvatarFile($newAvatarPath);
            return back()->withErrors(['email' => 'Server Medigo sedang tidak bisa dihubungi. Perubahan belum disimpan.'])->withInput();
        }

        if ($beResponse->failed()) {
            $this->deleteAvatarFile($newAvatarPath);

            if ($beResponse->status() === 401) {
                session()->forget('user_backend_token');
                return back()->withErrors(['email' => 'Sesi login Anda sudah habis. Silakan coba simpan lagi.'])->withInput();
            }

            $errors = $beResponse->json('errors') ?? [];
            if (!empty($errors['current_password'][0])) {
                return back()->withErrors(['current_password' => $errors['current_password'][0]])->withInput();
            }
            foreach (['email' => ['email', 'email_user'], 'name' => ['name', 'nama_user'], 'new_password' => ['new_password', 'password'], 'phone' => ['phone', 'no_telp'], 'birth_date' => ['birth_date', 'tanggal_lahir']] as $field => $keys) {
                foreach ($keys as $key) {
                    if (!empty($errors[$key][0])) {
                        return back()->withErrors([$field => $errors[$key][0]])->withInput();
                    }
                }
            }

            return back()->withErrors(['email' => $beResponse->json('message') ?? 'Gagal menyimpan profil. Coba lagi.'])->withInput();
        }

        // 4. Berhasil: perbarui data user di session dengan data terbaru dari backend
        $updatedBeUser = $beResponse->json('data');
        if (is_array($updatedBeUser) && !empty($updatedBeUser['id_user'])) {
            session(['user_backend_data' => $updatedBeUser]);
            \App\Auth\BackendSessionUserProvider::refreshRemembered();
        }

        // Foto lama baru dihapus setelah backend berhasil menyimpan foto baru
        if ($newAvatarPath !== null && $user->avatar !== $newAvatarPath) {
            $this->deleteAvatarFile($user->avatar);
        }

        return redirect()->route('profile')->with('success', 'Profil Anda berhasil diperbarui!');
    }

    private function ensureAvatarDir(): void
    {
        $uploadDir = public_path('uploads/avatars');
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
    }

    private function deleteAvatarFile(?string $path): void
    {
        if (!empty($path) && str_starts_with($path, 'uploads/avatars/') && !str_contains($path, '..') && file_exists(public_path($path))) {
            @unlink(public_path($path));
        }
    }
}
