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
            $token = session('user_backend_token');
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
                        $myDiscussions = $discResponse->json('data') ?? [];
                        $stats['discussions_count'] = count($myDiscussions);
                    }

                    // Fetch user forum comments (API Baru)
                    $forumCommResponse = Http::timeout(3)->withToken($token)->get("{$backendUrl}/api/user/comments");
                    if ($forumCommResponse->successful()) {
                        $myForumComments = $forumCommResponse->json('data') ?? [];
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

        $token = session('user_backend_token');
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
        $query = \Illuminate\Support\Facades\DB::table('faskes_comments')
            ->join('faskes', 'faskes_comments.faskes_id', '=', 'faskes.id')
            ->where('faskes_comments.user_id', $user->id);

        $totalReviews = $query->count();
        $reviews = $query->select(
                'faskes_comments.*',
                'faskes.nama_faskes',
                'faskes.tipe as faskes_tipe',
                'faskes.alamat as faskes_alamat'
            )
            ->orderBy('faskes_comments.created_at', 'desc')
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

            return [
                'id' => $item->id,
                'faskes_id' => $item->faskes_id,
                'nama_faskes' => $nama,
                'type_slug' => $typeSlug,
                'rating' => (int)$item->rating,
                'body' => $item->body,
                'created_at' => $item->created_at,
                'created_at_human' => \Carbon\Carbon::parse($item->created_at)->diffForHumans(),
                'faskes_url' => url('/faskes/' . $item->faskes_id),
            ];
        });

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

        $user->name = $validated['name'];

        if (!empty($validated['new_password'])) {
            if (!Hash::check($validated['current_password'], $user->password)) {
                return back()->withErrors(['current_password' => 'Kata sandi saat ini tidak sesuai.'])->withInput();
            }
            $user->password = Hash::make($validated['new_password']);
        }

        $user->save();

        return redirect()->route('profile')->with('success', 'Profil Anda berhasil diperbarui!');
    }
}