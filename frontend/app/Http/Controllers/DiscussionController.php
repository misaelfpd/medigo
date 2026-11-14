<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiscussionController extends Controller
{
    public function index(Request $request)
    {
        $rawBackendUrl = config('services.medigo.url', env('MEDIGO_BE', 'http://127.0.0.1:8005'));
        $backendUrl = rtrim(str_replace('localhost', '127.0.0.1', $rawBackendUrl), '/');

        $discussions = [];
        $pagination = [];
        
        $category = $request->query('category');
        $tab = $request->query('tab');
        $page = max(1, (int) $request->query('page', 1));
        $sort = $request->query('sort');
        $filter = $request->query('filter');

        $queryParams = ['page' => $page];

        if ($category) {
            $queryParams['category'] = $category;
        }

        // Filter diskusi yang menyebut (mention) 1 faskes tertentu & urutan
        $faskesFilterId = $request->query('faskes_id');
        if ($faskesFilterId && ctype_digit((string) $faskesFilterId)) {
            $queryParams['faskes_id'] = (int) $faskesFilterId;
        } else {
            $faskesFilterId = null;
        }
        if ($sort === 'terpopuler') {
            $queryParams['sort'] = 'terpopuler';
        }

        // Pastikan parameter ini ditangkap di Backend Controller jika user membuka tab Diskusi Saya
        if ($tab === 'my_discussions' && Auth::check()) {
            $queryParams['id_user'] = Auth::user()->id_user ?? Auth::id();
        }

        // ==============================================================================
        // DATA DINAMIS UNTUK SIDEBAR (Hilangkan Hardcode di View)
        // Note: Idealnya data ini didapat dari endpoint API Backend (misal: /api/categories & /api/trending)
        // ==============================================================================
        $sidebarCategories = [
            ['name' => 'Ulasan Rumah Sakit', 'count' => '1.2k'],
            ['name' => 'BPJS', 'count' => '856'],
            ['name' => 'Umum', 'count' => '2.4k'],
            ['name' => 'Tips Kesehatan', 'count' => '5.1k'],
        ];

        $trendingDiscussions = [
            [
                'title' => 'Proses pindah faskes 1 online terbaru 2024', 
                'replies_count' => 342, 
                'url' => '#' // Ganti dengan route('discussions.show', id) jika ada id
            ],
            [
                'title' => 'Rekomendasi dokter anak di Jakarta Selatan', 
                'replies_count' => 128, 
                'url' => '#'
            ],
            [
                'title' => 'Pengalaman operasi amandel pakai BPJS', 
                'replies_count' => 89, 
                'url' => '#'
            ],
        ];

        try {
            // Kirim token (jika login) supaya backend bisa mengisi liked_by_me
            $listToken = Auth::check() ? $this->getBackendToken() : null;
            $listHttp = Http::timeout(4);
            if ($listToken) {
                $listHttp = $listHttp->withToken($listToken);
            }
            $response = $listHttp->get("{$backendUrl}/api/discussions", $queryParams);

            if ($response->successful()) {
                $body = $response->json();
                $rawItems = $body['data']['data'] ?? [];
                
                // Avatar penulis diambil dari backend (author_avatar); frontend tidak punya tabel user sendiri
                $userAvatarsByEmail = [];

                $discussions = collect($rawItems)->map(function ($item) use ($userAvatarsByEmail) {
                    $item = (array) $item;
                    $id_diskusi = $item['id_diskusi'] ?? $item['id'] ?? null;
                    $judul = $item['judul_diskusi'] ?? $item['title'] ?? '';
                    $isi = $item['isi_diskusi'] ?? $item['body'] ?? $item['content'] ?? '';
                    $kategori = $item['kategori'] ?? $item['category'] ?? 'Umum';
                    $gambar = $item['gambar_diskusi'] ?? $item['image_path'] ?? $item['image'] ?? null;
                    $id_user = $item['id_user'] ?? $item['user_id'] ?? null;
                    $authorEmail = $item['author_email'] ?? $item['email_user'] ?? $item['email'] ?? null;
                    $authorName = $item['author_name'] ?? $item['nama_user'] ?? null;

                    // Resolusi avatar penulis diskusi
                    $authorAvatar = null;
                    if (Auth::check() && !empty(Auth::user()->email) && strtolower(Auth::user()->email) === strtolower($authorEmail ?? '')) {
                        $authorAvatar = Auth::user()->avatar;
                    } elseif ($authorEmail && isset($userAvatarsByEmail[$authorEmail])) {
                        $authorAvatar = $userAvatarsByEmail[$authorEmail];
                    } else {
                        $authorAvatar = $item['author_avatar'] ?? ($item['avatar'] ?? null);
                    }

                    return array_merge($item, [
                        'id_diskusi' => $id_diskusi,
                        'id' => $id_diskusi,
                        
                        'judul_diskusi' => $judul,
                        'title' => $judul,
                        
                        'isi_diskusi' => $isi,
                        'body' => $isi,
                        'content' => $isi,
                        
                        'kategori' => $kategori,
                        'category' => $kategori,
                        
                        'gambar_diskusi' => $gambar,
                        'image_path' => $gambar,
                        
                        'id_user' => $id_user,
                        'user_id' => $id_user,

                        'author_name' => $authorName,
                        'nama_user' => $authorName,
                        
                        'author_avatar' => $authorAvatar,
                    ]);
                })->all();

                $pagination = $body['data'] ?? []; 
            }
        } catch (\Exception $e) {
            Log::info('Discussions fetch fallback: ' . $e->getMessage());
        }

        // Filter Faskes Terdekat untuk Forum
        if ($filter === 'terdekat') {
            try {
                $lat = $request->query('lat') ?? $request->cookie('medigo_user_lat') ?? session('user_lat');
                $lng = $request->query('lng') ?? $request->cookie('medigo_user_lng') ?? session('user_lng');

                // Jika lat & lng kosong, gunakan titik pusat Jabodetabek (-6.2088, 106.8456)
                if (empty($lat) || empty($lng)) {
                    $lat = -6.2088;
                    $lng = 106.8456;
                }

                $nearbyParams = [
                    'limit' => 20,
                    'lat' => (float) $lat,
                    'lng' => (float) $lng,
                ];
                $nearbyRes = Http::timeout(5)->acceptJson()->get("{$backendUrl}/api/forum/diskusi-terdekat", $nearbyParams);
                if ($nearbyRes->successful()) {
                    $rawNearby = $nearbyRes->json('data') ?? [];
                    $discussions = collect($rawNearby)->map(function ($item) {
                        $item = (array) $item;
                        $f = $item['faskes'] ?? [];
                        $authorName = !empty($item['author_name']) ? $item['author_name'] : (!empty($item['nama_user']) ? $item['nama_user'] : 'Pengguna');
                        return [
                            'id_diskusi' => $item['id_diskusi'] ?? 0,
                            'id' => $item['id_diskusi'] ?? 0,
                            'judul_diskusi' => $item['judul_diskusi'] ?? '',
                            'isi_diskusi' => $item['isi_diskusi'] ?? '',
                            'kategori' => !empty($item['kategori']) ? $item['kategori'] : ($item['category'] ?? 'Umum'),
                            'category' => !empty($item['kategori']) ? $item['kategori'] : ($item['category'] ?? 'Umum'),
                            'comments_count' => (int) ($item['comments_count'] ?? 0),
                            'likes_count' => (int) ($item['likes_count'] ?? 0),
                            'mentioned_faskes' => $f,
                            'author_name' => $authorName,
                            'nama_user' => $authorName,
                            'author_avatar' => $item['author_avatar'] ?? null,
                            'created_at' => $item['created_at'] ?? now()->toIso8601String(),
                            'distance_km' => $f['distance_km'] ?? null,
                        ];
                    })->all();

                    $pagination = [
                        'current_page' => 1,
                        'last_page' => 1,
                        'total' => count($discussions),
                    ];
                }
            } catch (\Exception $e) {
                Log::warning('Nearby discussions fetch failed in index: ' . $e->getMessage());
            }
        }

        // Dukungan AJAX Lazy Loading
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => $discussions,
                'pagination' => [
                    'current_page' => (int) ($pagination['current_page'] ?? $page),
                    'last_page' => (int) ($pagination['last_page'] ?? 1),
                    'has_more' => (int) ($pagination['current_page'] ?? $page) < (int) ($pagination['last_page'] ?? 1),
                ],
            ]);
        }

        // Pass variabel sidebarCategories dan trendingDiscussions ke View
        return view('users.discussion', compact(
            'discussions', 
            'category', 
            'tab', 
            'pagination', 
            'sidebarCategories', 
            'trendingDiscussions',
            'faskesFilterId',
            'sort',
            'filter'
        ));
    }

    public function create()
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('warning', 'Silakan masuk terlebih dahulu untuk membuat topik diskusi baru.');
        }

        return view('users.discussion_create');
    }

    protected function getBackendToken()
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
                    'email' => Auth::user()->email_user ?? Auth::user()->email,
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
            Log::warning('Failed obtaining backend token: ' . $e->getMessage());
        }

        return null;
    }

    public function store(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('warning', 'Silakan masuk terlebih dahulu untuk membuat topik diskusi baru.');
        }

        $request->validate([
            'title'    => 'required|string|max:255',
            'content'  => 'required|string',
            'category' => 'nullable|string',
            'faskes_id' => 'nullable|integer|min:1',
            'images'   => 'nullable|array|max:3',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:5120',
        ], [
            'title.required'   => 'Judul pembahasan wajib diisi.',
            'content.required' => 'Isi diskusi wajib diisi.',
            'images.max'       => 'Maksimal 3 foto yang dapat dilampirkan.',
        ]);

        $token = $this->getBackendToken();
        if (!$token) {
            return back()->withInput()->with('error', 'Gagal memvalidasi sesi login Anda dengan server backend. Silakan coba kembali.');
        }

        try {
            $rawBackendUrl = config('services.medigo.url', env('MEDIGO_BE', 'http://127.0.0.1:8005'));
            $backendUrl = rtrim(str_replace('localhost', '127.0.0.1', $rawBackendUrl), '/');

            $http = Http::timeout(15)->withToken($token);

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    if ($image && $image->isValid()) {
                        $http->attach(
                            'images[]',
                            file_get_contents($image->getPathname()),
                            $image->getClientOriginalName()
                        );
                    }
                }
            }

            // Payload POST diskusi ke backend (pastikan validasi di Backend masih menerima 'title' & 'content')
            $payload = [
                'title'    => $request->input('title'),
                'content'  => $request->input('content'),
                'category' => $request->input('category', 'Umum'),
            ];
            // Mention 1 faskes (opsional) dari pencarian faskes di form
            if ($request->filled('faskes_id') && ctype_digit((string) $request->input('faskes_id'))) {
                $payload['faskes_id'] = (int) $request->input('faskes_id');
            }

            $response = $http->post("{$backendUrl}/api/discussions", $payload);

            if ($response->successful()) {
                $resData = $response->json();
                $newId = $resData['discussion_id'] ?? $resData['id_diskusi'] ?? null;
                
                if ($newId) {
                    return redirect()->route('discussions.show', $newId)->with('success', 'Topik diskusi Anda berhasil dipublikasikan!');
                }
                return redirect()->route('discussions')->with('success', 'Topik diskusi Anda berhasil dipublikasikan!');
            } else {
                $errorMsg = $response->json('message') ?? 'Gagal membuat diskusi di server.';
                return back()->withInput()->with('error', $errorMsg);
            }
        } catch (\Exception $e) {
            Log::warning('Failed posting discussion to backend: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Terjadi kendala saat menghubungi server. Silakan coba lagi.');
        }
    }

    public function report(Request $request, $type, $target_id)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('warning', 'Silakan masuk terlebih dahulu untuk melaporkan.');
        }

        if (!in_array($type, ['discussion', 'comment'], true) || !ctype_digit((string) $target_id)) {
            return back()->with('error', 'Laporan tidak valid.');
        }

        $request->validate([
            'reason' => 'required|string|max:500',
            'notes' => 'nullable|string|max:500',
        ], [
            'reason.required' => 'Pilih atau tuliskan alasan pelaporan.',
            'notes.max' => 'Catatan tambahan maksimal 500 karakter.',
        ]);

        $token = $this->getBackendToken();
        if (!$token) {
            return redirect()->route('login')->with('warning', 'Sesi login berakhir. Silakan masuk ulang.');
        }

        try {
            // 'comment' = balasan/komentar diskusi (tabel tanggapan_diskusi)
            $endpoint = $type === 'comment'
                ? $this->backendBaseUrl() . "/api/discussion-comments/{$target_id}/report"
                : $this->backendBaseUrl() . "/api/discussions/{$target_id}/report";

            $response = Http::timeout(6)->withToken($token)->acceptJson()->post($endpoint, [
                'reason' => $request->input('reason'),
                'notes' => $request->input('notes'),
            ]);

            if ($response->successful()) {
                return back()->with('success', $response->json('message') ?: 'Laporan berhasil dikirim dan akan ditinjau.');
            }

            $message = $response->json('message');
            if ($response->status() === 422 && is_array($response->json('errors'))) {
                $message = collect($response->json('errors'))->flatten()->first() ?: $message;
            }

            return back()->with('error', $message ?: 'Gagal mengirim laporan. Silakan coba lagi nanti.');
        } catch (\Exception $e) {
            Log::warning("Failed posting {$type} report: " . $e->getMessage());
        }

        return back()->with('error', 'Gagal mengirim laporan. Silakan coba lagi nanti.');
    }

    public function show($id_diskusi)
    {
        $rawBackendUrl = config('services.medigo.url', env('MEDIGO_BE', 'http://127.0.0.1:8005'));
        $backendUrl = rtrim(str_replace('localhost', '127.0.0.1', $rawBackendUrl), '/');

        $discussion = null;
        $comments = [];
        $loggedInUserId = null;

        try {
            $token = $this->getBackendToken();
            
            $http = Http::timeout(4);
            if ($token) {
                $http = $http->withToken($token);
            }

            // Hit backend API menggunakan $id_diskusi
            $response = $http->get("{$backendUrl}/api/discussions/{$id_diskusi}");

            if ($response->successful()) {
                $responseBody = $response->json();
                $data = $responseBody['data'] ?? [];
                $rawDiscussion = $data['discussion'] ?? null;
                $rawComments = $data['comments'] ?? [];

                $userAvatarsByEmail = []; // Avatar penulis diambil dari backend (author_avatar)

                if ($rawDiscussion) {
                    $d = (array) $rawDiscussion;
                    $idVal = $d['id_diskusi'] ?? $d['id'] ?? $id_diskusi;
                    $titleVal = $d['judul_diskusi'] ?? $d['title'] ?? '';
                    $bodyVal = $d['isi_diskusi'] ?? $d['body'] ?? $d['content'] ?? '';
                    $catVal = $d['kategori'] ?? $d['category'] ?? 'Umum';
                    $imgVal = $d['gambar_diskusi'] ?? $d['image_path'] ?? null;
                    $usrVal = $d['id_user'] ?? $d['user_id'] ?? null;
                    $authorNameVal = $d['nama_user'] ?? $d['author_name'] ?? 'Pengguna';
                    $authorEmailVal = $d['email_user'] ?? $d['author_email'] ?? null;

                    // Resolusi avatar pembuat diskusi
                    $authorAvatarVal = null;
                    if (Auth::check() && !empty(Auth::user()->email) && strtolower(Auth::user()->email) === strtolower($authorEmailVal ?? '')) {
                        $authorAvatarVal = Auth::user()->avatar;
                    } elseif ($authorEmailVal && isset($userAvatarsByEmail[$authorEmailVal])) {
                        $authorAvatarVal = $userAvatarsByEmail[$authorEmailVal];
                    } else {
                        $authorAvatarVal = $d['author_avatar'] ?? ($d['avatar'] ?? null);
                    }

                    $discussion = array_merge($d, [
                        'id_diskusi' => $idVal,
                        'id' => $idVal,
                        'judul_diskusi' => $titleVal,
                        'title' => $titleVal,
                        'isi_diskusi' => $bodyVal,
                        'body' => $bodyVal,
                        'kategori' => $catVal,
                        'category' => $catVal,
                        'gambar_diskusi' => $imgVal,
                        'image_path' => $imgVal,
                        'id_user' => $usrVal,
                        'user_id' => $usrVal,
                        'nama_user' => $authorNameVal,
                        'author_name' => $authorNameVal,
                        'email_user' => $authorEmailVal,
                        'author_email' => $authorEmailVal,
                        'author_avatar' => $authorAvatarVal,
                    ]);
                }

                $currentUserId = Auth::user()->id_user ?? Auth::id();

                $commentMap = [];
                foreach ($rawComments as $comment) {
                    $c = (array) $comment;
                    $cId = $c['id_tanggapan'] ?? $c['id'] ?? null;
                    $cDiscId = $c['id_diskusi'] ?? $c['discussion_id'] ?? $id_diskusi;
                    $cParentId = $c['id_parent'] ?? $c['parent_id'] ?? null;
                    $cUserId = $c['id_user'] ?? $c['user_id'] ?? null;
                    $cBody = $c['isi_tanggapan'] ?? $c['body'] ?? $c['content'] ?? '';
                    $cImage = $c['gambar_tanggapan'] ?? $c['image_path'] ?? null;
                    $cName = $c['nama_user'] ?? $c['commentator_name'] ?? 'Pengguna';
                    $cEmail = $c['email_user'] ?? $c['commentator_email'] ?? null;

                    // Resolusi avatar komentator
                    $cAvatar = null;
                    if (Auth::check() && !empty(Auth::user()->email) && strtolower(Auth::user()->email) === strtolower($cEmail ?? '')) {
                        $cAvatar = Auth::user()->avatar;
                    } elseif ($cEmail && isset($userAvatarsByEmail[$cEmail])) {
                        $cAvatar = $userAvatarsByEmail[$cEmail];
                    } else {
                        $cAvatar = $c['commentator_avatar'] ?? ($c['avatar'] ?? null);
                    }

                    $c['id_tanggapan'] = $cId;
                    $c['id'] = $cId;
                    
                    $c['id_diskusi'] = $cDiscId;
                    $c['discussion_id'] = $cDiscId;
                    
                    $c['id_parent'] = $cParentId;
                    $c['parent_id'] = $cParentId;
                    
                    $c['id_user'] = $cUserId;
                    $c['user_id'] = $cUserId;
                    
                    $c['isi_tanggapan'] = $cBody;
                    $c['body'] = $cBody;
                    $c['content'] = $cBody;
                    
                    $c['gambar_tanggapan'] = $cImage;
                    $c['image_path'] = $cImage;
                    
                    $c['nama_user'] = $cName;
                    $c['commentator_name'] = $cName;
                    
                    $c['email_user'] = $cEmail;
                    $c['commentator_email'] = $cEmail;
                    $c['commentator_avatar'] = $cAvatar;
                    $c['avatar'] = $cAvatar;
                    
                    $c['replies'] = []; 
                    
                    $commentMap[$cId] = $c;
                }

                foreach ($commentMap as $cid => $c) {
                    $parentId = $c['id_parent'];
                    if ($parentId && isset($commentMap[$parentId])) {
                        $commentMap[$parentId]['replies'][] = &$commentMap[$cid];
                    }
                }

                $rootComments = [];
                foreach ($commentMap as $cid => $c) {
                    if (empty($c['id_parent'])) {
                        $rootComments[] = $c;
                    }
                }

                if ($currentUserId && !empty($rootComments)) {
                    usort($rootComments, function ($a, $b) use ($currentUserId) {
                        $aIsMine = (($a['id_user'] ?? 0) == $currentUserId);
                        $bIsMine = (($b['id_user'] ?? 0) == $currentUserId);

                        $aHasMyReply = collect($a['replies'])->contains('id_user', $currentUserId);
                        $bHasMyReply = collect($b['replies'])->contains('id_user', $currentUserId);

                        $scoreA = ($aIsMine ? 2 : 0) + ($aHasMyReply ? 1 : 0);
                        $scoreB = ($bIsMine ? 2 : 0) + ($bHasMyReply ? 1 : 0);

                        return $scoreB <=> $scoreA;
                    });
                }

                $comments = $rootComments;
            }
        } catch (\Exception $e) {
            Log::info('Discussion detail fetch fallback: ' . $e->getMessage());
        }

        if (!$discussion) {
            abort(404, 'Diskusi tidak ditemukan.');
        }

        return view('users.discussion_detail', compact('discussion', 'comments'));
    }

public function reply(Request $request, $id_diskusi)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('warning', 'Silakan masuk terlebih dahulu untuk membalas diskusi.');
        }

        $request->validate([
            'content'   => 'required|string|max:1000',
            'image'     => 'nullable|image|mimes:jpeg,png,jpg|max:5120', 
            'id_parent' => 'nullable|integer', 
        ]);

        $token = $this->getBackendToken();
        if ($token) {
            try {
                $rawBackendUrl = config('services.medigo.url', env('MEDIGO_BE', 'http://127.0.0.1:8005'));
                $backendUrl = rtrim(str_replace('localhost', '127.0.0.1', $rawBackendUrl), '/');
                $http = Http::timeout(10)->withToken($token);

                // SESUAIKAN PAYLOAD DENGAN REQUEST DI BACKEND COMMENT CONTROLLER
                $payload = [
                    'body'      => $request->input('content'), // Sesuai dengan $request->validate(['body' => ...]) di backend
                    'parent_id' => $request->input('id_parent') ?? $request->input('parent_id'), // Sesuai dengan request backend
                ];

                if ($request->hasFile('image')) {
                    $image = $request->file('image');
                    $response = $http->attach(
                        'image', // Sesuai dengan $request->hasFile('image') di backend
                        file_get_contents($image->getPathname()), 
                        $image->getClientOriginalName()
                    )->post("{$backendUrl}/api/discussions/{$id_diskusi}/comments", $payload);
                } else {
                    $response = $http->post("{$backendUrl}/api/discussions/{$id_diskusi}/comments", $payload);
                }
                
                if ($response->successful()) {
                    return redirect()->route('discussions.show', $id_diskusi)->with('success', 'Balasan Anda berhasil dikirim!');
                }

                // Tambahkan log error jika backend mengembalikan status gagal (contoh: 404 atau 422)
                $errorMsg = $response->json('message') ?? 'Gagal mengirim balasan.';
                return redirect()->route('discussions.show', $id_diskusi)->with('error', $errorMsg);
                
            } catch (\Exception $e) {
                Log::warning('Failed posting discussion reply to backend: ' . $e->getMessage());
            }
        }

        return redirect()->route('discussions.show', $id_diskusi)->with('error', 'Gagal mengirim balasan.');
    }

    public function destroyComment(Request $request, $id_tanggapan = null)
    {
        if (!Auth::check()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
            }
            return redirect()->route('login')->with('warning', 'Silakan masuk terlebih dahulu.');
        }

        $token = $this->getBackendToken();
        if ($token) {
            try {
                $rawBackendUrl = config('services.medigo.url', env('MEDIGO_BE', 'http://127.0.0.1:8005'));
                $backendUrl = rtrim(str_replace('localhost', '127.0.0.1', $rawBackendUrl), '/');

                $response = Http::withToken($token)->delete("{$backendUrl}/api/discussion-comments/{$id_tanggapan}");

                if ($response->successful()) {
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json(['status' => 'success', 'message' => 'Komentar berhasil dihapus']);
                    }
                    return back()->with('success', 'Komentar berhasil dihapus.');
                }

                $errorMsg = $response->json('message') ?? 'Gagal menghapus komentar.';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['status' => 'error', 'message' => $errorMsg], $response->status());
                }
                return back()->with('error', $errorMsg);
            } catch (\Exception $e) {
                Log::error('Gagal menghapus komentar dari Frontend: ' . $e->getMessage());
            }
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'error', 'message' => 'Gagal menghapus komentar.'], 400);
        }
        return back()->with('error', 'Gagal menghapus komentar atau Anda tidak memiliki akses.');
    }

    public function destroyDiscussion(Request $request, $id_diskusi)
    {
        if (!Auth::check()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
            }
            return redirect()->route('login')->with('warning', 'Silakan masuk terlebih dahulu.');
        }

        $token = $this->getBackendToken();
        if ($token) {
            try {
                $rawBackendUrl = config('services.medigo.url', env('MEDIGO_BE', 'http://127.0.0.1:8005'));
                $backendUrl = rtrim(str_replace('localhost', '127.0.0.1', $rawBackendUrl), '/');

                $response = Http::withToken($token)->delete("{$backendUrl}/api/discussions/{$id_diskusi}");

                if ($response->successful()) {
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json(['status' => 'success', 'message' => 'Diskusi berhasil dihapus']);
                    }
                    return redirect()->route('discussions')->with('success', 'Diskusi berhasil dihapus.');
                }

                $errorMsg = $response->json('message') ?? 'Gagal menghapus diskusi.';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['status' => 'error', 'message' => $errorMsg], $response->status());
                }
                return back()->with('error', $errorMsg);
            } catch (\Exception $e) {
                Log::error('Gagal menghapus diskusi dari Frontend: ' . $e->getMessage());
            }
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'error', 'message' => 'Gagal menghapus diskusi.'], 400);
        }
        return back()->with('error', 'Gagal menghapus diskusi atau Anda tidak memiliki akses.');
    }

    private function backendBaseUrl(): string
    {
        $rawBackendUrl = config('services.medigo.url', env('MEDIGO_BE', 'http://127.0.0.1:8005'));

        return rtrim(str_replace('localhost', '127.0.0.1', $rawBackendUrl), '/');
    }

    /**
     * Rekomendasi faskes terdekat untuk sidebar forum (proxy ke backend).
     */
    public function nearbyFaskes(Request $request)
    {
        $validated = $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        try {
            $response = Http::timeout(6)->get($this->backendBaseUrl() . '/api/forum/faskes-terdekat', [
                'lat' => $validated['lat'],
                'lng' => $validated['lng'],
                'limit' => 5,
            ]);

            return response()->json($response->json() ?? ['status' => 'error', 'data' => []], $response->status());
        } catch (\Exception $e) {
            Log::warning('Nearby faskes fetch failed: ' . $e->getMessage());

            return response()->json(['status' => 'error', 'message' => 'Server tidak dapat dihubungi.', 'data' => []], 503);
        }
    }

    /**
     * Diskusi yang membahas faskes di sekitar user untuk sidebar forum (proxy ke backend).
     * Tanpa lat/lng, backend mengembalikan diskusi faskes terpopuler.
     */
    public function nearbyDiscussions(Request $request)
    {
        $validated = $request->validate([
            'lat' => 'nullable|required_with:lng|numeric|between:-90,90',
            'lng' => 'nullable|required_with:lat|numeric|between:-180,180',
        ]);

        $params = ['limit' => 5];
        if (isset($validated['lat'], $validated['lng'])) {
            $params['lat'] = $validated['lat'];
            $params['lng'] = $validated['lng'];
        }

        try {
            $response = Http::timeout(6)->acceptJson()->get($this->backendBaseUrl() . '/api/forum/diskusi-terdekat', $params);

            return response()->json($response->json() ?? ['status' => 'error', 'data' => []], $response->status());
        } catch (\Exception $e) {
            Log::warning('Nearby discussions fetch failed: ' . $e->getMessage());

            return response()->json(['status' => 'error', 'message' => 'Server tidak dapat dihubungi.', 'data' => []], 503);
        }
    }

    /**
     * Cari faskes untuk di-mention pada judul diskusi (proxy ke backend).
     */
    public function mentionSearch(Request $request)
    {
        try {
            $response = Http::timeout(6)->get($this->backendBaseUrl() . '/api/faskes/mention-search', [
                'q' => (string) $request->query('q', ''),
                'limit' => 8,
            ]);

            return response()->json($response->json() ?? ['status' => 'error', 'data' => []], $response->status());
        } catch (\Exception $e) {
            Log::warning('Mention search failed: ' . $e->getMessage());

            return response()->json(['status' => 'error', 'message' => 'Server tidak dapat dihubungi.', 'data' => []], 503);
        }
    }

    /**
     * Like / unlike diskusi atau komentar diskusi (proxy ke backend).
     * action=like -> POST, action=unlike -> DELETE.
     */
    public function like(Request $request, $type, $id)
    {
        if (!Auth::check()) {
            return response()->json(['status' => 'error', 'message' => 'Silakan masuk terlebih dahulu untuk menyukai.'], 401);
        }

        if (!in_array($type, ['discussion', 'comment'], true) || !ctype_digit((string) $id)) {
            return response()->json(['status' => 'error', 'message' => 'Permintaan tidak valid.'], 422);
        }

        $token = $this->getBackendToken();
        if (!$token) {
            return response()->json(['status' => 'error', 'message' => 'Sesi login tidak valid. Silakan masuk ulang.'], 401);
        }

        $path = $type === 'discussion' ? "/api/discussions/{$id}/like" : "/api/discussion-comments/{$id}/like";
        $isUnlike = $request->input('action') === 'unlike';

        try {
            $http = Http::timeout(6)->withToken($token)->acceptJson();
            $response = $isUnlike
                ? $http->delete($this->backendBaseUrl() . $path)
                : $http->post($this->backendBaseUrl() . $path);

            return response()->json($response->json() ?? ['status' => 'error'], $response->status());
        } catch (\Exception $e) {
            Log::warning('Like request failed: ' . $e->getMessage());

            return response()->json(['status' => 'error', 'message' => 'Server tidak dapat dihubungi.'], 503);
        }
    }
}