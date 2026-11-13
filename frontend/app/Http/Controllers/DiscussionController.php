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
        $page = $request->query('page', 1);

        $queryParams = ['page' => $page];

        if ($category) {
            $queryParams['category'] = $category;
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
            $response = Http::timeout(4)->get("{$backendUrl}/api/discussions", $queryParams);

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
                        
                        'author_avatar' => $authorAvatar,
                    ]);
                })->all();

                $pagination = $body['data'] ?? []; 
            }
        } catch (\Exception $e) {
            Log::info('Discussions fetch fallback: ' . $e->getMessage());
        }

        // Pass variabel sidebarCategories dan trendingDiscussions ke View
        return view('users.discussion', compact(
            'discussions', 
            'category', 
            'tab', 
            'pagination', 
            'sidebarCategories', 
            'trendingDiscussions'
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
            $response = $http->post("{$backendUrl}/api/discussions", [
                'title'    => $request->input('title'),
                'content'  => $request->input('content'),
                'category' => $request->input('category', 'Umum'),
            ]);

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

        $request->validate(['reason' => 'required|string|max:500']);

        $token = $this->getBackendToken();
        if ($token) {
            try {
                $rawBackendUrl = config('services.medigo.url', env('MEDIGO_BE', 'http://127.0.0.1:8005'));
                $backendUrl = rtrim(str_replace('localhost', '127.0.0.1', $rawBackendUrl), '/');

                $endpoint = $type === 'comment' 
                    ? "{$backendUrl}/api/comments/{$target_id}/report" 
                    : "{$backendUrl}/api/discussions/{$target_id}/report";

                $response = Http::timeout(5)->withToken($token)->post($endpoint, [
                    'reason' => $request->input('reason')
                ]);
                
                if ($response->successful()) {
                    return back()->with('success', 'Laporan berhasil dikirim dan akan ditinjau.');
                }
            } catch (\Exception $e) {
                Log::warning("Failed posting {$type} report: " . $e->getMessage());
            }
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
}