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
            'current_password' => ['nullable', 'required_with:new_password', 'string'],
            'new_password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => 'Nama lengkap tidak boleh kosong.',
            'current_password.required_with' => 'Kata sandi saat ini wajib diisi untuk mengubah kata sandi.',
            'new_password.min' => 'Kata sandi baru minimal 8 karakter.',
            'new_password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
        ]);

        if (!empty($validated['new_password'])) {
            if (!Hash::check($validated['current_password'], $user->password)) {
                return back()->withErrors(['current_password' => 'Kata sandi saat ini tidak sesuai.'])->withInput();
            }
        }

        // Sinkronisasi pembaruan profil ke Backend API
        $token = $this->getBackendToken();
        if ($token) {
            try {
                $rawBackendUrl = config('services.medigo.url', env('MEDIGO_BE', 'http://127.0.0.1:8005'));
                $backendUrl = rtrim(str_replace('localhost', '127.0.0.1', $rawBackendUrl), '/');

                $payload = [
                    'name' => $validated['name'],
                ];
                if (!empty($validated['new_password'])) {
                    $payload['current_password'] = $validated['current_password'];
                    $payload['new_password'] = $validated['new_password'];
                }

                $beResponse = Http::timeout(5)
                    ->withToken($token)
                    ->put("{$backendUrl}/api/user", $payload);

                if ($beResponse->failed()) {
                    $errData = $beResponse->json();
                    if (!empty($errData['errors']['current_password'])) {
                        return back()->withErrors(['current_password' => $errData['errors']['current_password'][0]])->withInput();
                    }
                    if (!empty($errData['message'])) {
                        Log::warning('Backend profile update failed: ' . $errData['message']);
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

        // Simpan perubahan ke database lokal Frontend
        $user->name = $validated['name'];
        if (!empty($validated['new_password'])) {
            $user->password = Hash::make($validated['new_password']);
        }
        $user->save();

        return redirect()->route('profile')->with('success', 'Profil Anda berhasil diperbarui!');
    }
}