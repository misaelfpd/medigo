<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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

        // Direct database fallback if backend token is absent or call failed
        $offset = ($page - 1) * $limit;
        try {
            $query = \Illuminate\Support\Facades\DB::table('komentar')
                ->join('faskes', 'komentar.id_faskes', '=', 'faskes.id_faskes')
                ->where('komentar.id_user', $user->id);

            $totalReviews = $query->count();
            $reviews = $query->select(
                    'komentar.*',
                    'faskes.nama_faskes',
                    'faskes.jenis_faskes as faskes_tipe',
                    'faskes.alamat_faskes as faskes_alamat'
                )
                ->orderBy('komentar.created_at', 'desc')
                ->offset($offset)
                ->limit($limit)
                ->get();

            $formatted = $reviews->map(function ($item) {
                $typeSlug = 'hospital';
                $tipeLower = strtolower($item->faskes_tipe ?? '');
                if (str_contains($tipeLower, 'puskesmas')) {
                    $typeSlug = 'puskesmas';
                } elseif (str_contains($tipeLower, 'klinik') || str_contains($tipeLower, 'dokter')) {
                    $typeSlug = 'clinic';
                }

                $nama = trim($item->nama_faskes ?? '');
                if ($typeSlug === 'puskesmas' && !preg_match('/^(puskesmas|pkm)\b/i', $nama)) {
                    $nama = 'Puskesmas ' . $nama;
                }

                $reviewId = $item->id_komentar ?? ($item->id ?? null);
                $faskesId = $item->id_faskes ?? ($item->faskes_id ?? null);
                $body = $item->isi_komentar ?? ($item->body ?? '');

                return [
                    'id' => $reviewId,
                    'faskes_id' => $faskesId,
                    'nama_faskes' => $nama,
                    'type_slug' => $typeSlug,
                    'rating' => (int)$item->rating,
                    'body' => $body,
                    'created_at' => $item->created_at,
                    'created_at_human' => \Carbon\Carbon::parse($item->created_at)->diffForHumans(),
                    'faskes_url' => url('/faskes/' . $faskesId),
                ];
            });
        } catch (\Exception $e) {
            $formatted = collect([]);
            $totalReviews = 0;
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'reviews' => $formatted,
                'current_page' => $page,
                'per_page' => $limit,
                'total_reviews' => $totalReviews,
                'has_more' => ($offset + $limit) < $totalReviews,
            ]
        ]);
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
     * Update user profile information
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:50'],
            'birth_date' => ['nullable', 'date'],
            'current_password' => ['nullable', 'required_with:new_password', 'string'],
            'new_password' => ['nullable', 'string', 'min:8'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ], [
            'name.required' => 'Nama lengkap tidak boleh kosong.',
            'email.required' => 'Alamat email tidak boleh kosong.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah terdaftar oleh pengguna lain.',
            'current_password.required_with' => 'Kata sandi saat ini wajib diisi untuk mengubah kata sandi.',
            'new_password.min' => 'Kata sandi baru minimal 8 karakter.',
            'avatar.image' => 'File harus berupa gambar.',
            'avatar.mimes' => 'Format foto profil harus JPG, JPEG, atau PNG.',
            'avatar.max' => 'Ukuran foto profil tidak boleh melebihi 2MB.',
        ]);

        // 1. Cek & Proses Foto Profil Baru (Hasil Crop Base64 atau File Upload)
        $avatarChanged = false;
        $avatarCropped = $request->input('avatar_cropped');

        if (!empty($avatarCropped)) {
            // Validasi format data URL (hanya jpeg, jpg, png)
            if (!preg_match('/^data:image\/(jpeg|jpg|png);base64,/', $avatarCropped, $matches)) {
                return back()->withErrors(['avatar' => 'Format foto tidak valid. Gunakan format JPG, JPEG, atau PNG.'])->withInput();
            }

            $imgType = strtolower($matches[1]);
            $base64Data = substr($avatarCropped, strpos($avatarCropped, ',') + 1);
            $decodedData = base64_decode($base64Data);

            if ($decodedData === false) {
                return back()->withErrors(['avatar' => 'Gagal memproses gambar foto profil.'])->withInput();
            }

            // Validasi ukuran maksimal 2MB (2,097,152 bytes)
            if (strlen($decodedData) > 2 * 1024 * 1024) {
                return back()->withErrors(['avatar' => 'Ukuran foto profil tidak boleh melebihi 2MB.'])->withInput();
            }

            $uploadDir = public_path('uploads/avatars');
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $ext = ($imgType === 'png') ? 'png' : 'jpg';
            $filename = 'avatar_' . $user->id . '_' . time() . '.' . $ext;
            $destination = $uploadDir . DIRECTORY_SEPARATOR . $filename;

            file_put_contents($destination, $decodedData);

            // Hapus file avatar lama jika ada
            if (!empty($user->avatar) && file_exists(public_path($user->avatar)) && str_contains($user->avatar, 'uploads/avatars/')) {
                @unlink(public_path($user->avatar));
            }

            $user->avatar = 'uploads/avatars/' . $filename;
            $avatarChanged = true;
        } elseif ($request->hasFile('avatar')) {
            $uploadDir = public_path('uploads/avatars');
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $file = $request->file('avatar');
            $ext = $file->getClientOriginalExtension();
            $filename = 'avatar_' . $user->id . '_' . time() . '.' . $ext;
            $file->move($uploadDir, $filename);

            if (!empty($user->avatar) && file_exists(public_path($user->avatar)) && str_contains($user->avatar, 'uploads/avatars/')) {
                @unlink(public_path($user->avatar));
            }

            $user->avatar = 'uploads/avatars/' . $filename;
            $avatarChanged = true;
        }

        // 2. Cek apakah ada perubahan data teks
        $trimmedName = trim($validated['name']);
        $trimmedEmail = trim($validated['email']);
        $trimmedPhone = !empty($validated['phone']) ? trim($validated['phone']) : null;
        $formattedBirthDate = !empty($validated['birth_date']) ? \Carbon\Carbon::parse($validated['birth_date'])->format('Y-m-d') : null;
        $currentBirthDate = $user->birth_date ? \Carbon\Carbon::parse($user->birth_date)->format('Y-m-d') : null;
        $newPassword = $validated['new_password'] ?? null;

        $nameChanged = ($trimmedName !== $user->name);
        $emailChanged = (strtolower($trimmedEmail) !== strtolower($user->email));
        $phoneChanged = ($trimmedPhone !== $user->phone);
        $birthDateChanged = ($formattedBirthDate !== $currentBirthDate);
        $passwordChanged = !empty($newPassword);

        // Jika tidak ada perubahan sama sekali, tidak terjadi apa-apa
        if (!$nameChanged && !$emailChanged && !$phoneChanged && !$birthDateChanged && !$passwordChanged && !$avatarChanged) {
            return redirect()->route('profile');
        }

        // Jika ada perubahan password, cek kecocokan password lama
        if ($passwordChanged) {
            if (!Hash::check($validated['current_password'], $user->password)) {
                return back()->withErrors(['current_password' => 'Kata sandi saat ini tidak sesuai.'])->withInput();
            }
        }

        // 3. Sinkronisasi pembaruan profil ke Backend API
        $token = $this->getBackendToken();
        if ($token) {
            try {
                $rawBackendUrl = config('services.medigo.url', env('MEDIGO_BE', 'http://127.0.0.1:8005'));
                $backendUrl = rtrim(str_replace('localhost', '127.0.0.1', $rawBackendUrl), '/');

                $payload = [
                    'name' => $trimmedName,
                    'email' => $trimmedEmail,
                    'phone' => $trimmedPhone,
                    'birth_date' => $formattedBirthDate,
                ];
                if ($passwordChanged) {
                    $payload['current_password'] = $validated['current_password'];
                    $payload['new_password'] = $newPassword;
                }
                if ($avatarChanged) {
                    $payload['avatar'] = $user->avatar;
                }

                $beResponse = Http::timeout(5)
                    ->withToken($token)
                    ->put("{$backendUrl}/api/user", $payload);

                if ($beResponse->failed()) {
                    $errData = $beResponse->json();
                    if (!empty($errData['errors']['current_password'])) {
                        return back()->withErrors(['current_password' => $errData['errors']['current_password'][0]])->withInput();
                    }
                    if (!empty($errData['errors']['email'])) {
                        return back()->withErrors(['email' => $errData['errors']['email'][0]])->withInput();
                    }
                } else {
                    $updatedBeUser = $beResponse->json('data');
                    if ($updatedBeUser) {
                        session(['user_backend_data' => $updatedBeUser]);
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Backend profile sync error: ' . $e->getMessage());
            }
        }

        // 4. Simpan perubahan ke database lokal Frontend
        $user->name = $trimmedName;
        $user->email = $trimmedEmail;
        $user->phone = $trimmedPhone;
        $user->birth_date = $formattedBirthDate;
        if ($passwordChanged) {
            $user->password = Hash::make($newPassword);
        }
        $user->save();

        return redirect()->route('profile')->with('success', 'Profil Anda berhasil diperbarui!');
    }
}