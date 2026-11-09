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

        if ($tab === 'my_discussions' && Auth::check()) {
            $queryParams['user_id'] = Auth::id();
        }

        try {
            $response = Http::timeout(4)->get("{$backendUrl}/api/discussions", $queryParams);

            if ($response->successful()) {
                $body = $response->json();
                $rawItems = $body['data']['data'] ?? [];
                $discussions = collect($rawItems)->map(function ($item) {
                    $item = (array) $item;
                    $id = $item['id_diskusi'] ?? $item['id'] ?? null;
                    $title = $item['judul_diskusi'] ?? $item['title'] ?? '';
                    $body = $item['isi_diskusi'] ?? $item['body'] ?? $item['content'] ?? '';
                    $category = $item['kategori'] ?? $item['category'] ?? 'Umum';
                    $imagePath = $item['gambar_diskusi'] ?? $item['image_path'] ?? $item['image'] ?? null;
                    $userId = $item['id_user'] ?? $item['user_id'] ?? null;

                    return array_merge($item, [
                        'id' => $id,
                        'id_diskusi' => $id,
                        'title' => $title,
                        'judul_diskusi' => $title,
                        'body' => $body,
                        'isi_diskusi' => $body,
                        'content' => $body,
                        'category' => $category,
                        'kategori' => $category,
                        'image_path' => $imagePath,
                        'gambar_diskusi' => $imagePath,
                        'user_id' => $userId,
                        'id_user' => $userId,
                    ]);
                })->all();

                $pagination = $body['data'] ?? []; 
            }
        } catch (\Exception $e) {
            Log::info('Discussions fetch fallback: ' . $e->getMessage());
        }

        // Jika backend tidak mengembalikan data, biarkan $discussions tetap kosong []
        return view('users.discussion', compact('discussions', 'category', 'tab', 'pagination'));
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

            $response = $http->post("{$backendUrl}/api/discussions", [
                'title'    => $request->input('title'),
                'content'  => $request->input('content'),
                'category' => $request->input('category', 'Umum'),
            ]);

            if ($response->successful()) {
                $resData = $response->json();
                $newId = $resData['discussion_id'] ?? null;
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

    public function report(Request $request, $type, $id)
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
                    ? "{$backendUrl}/api/comments/{$id}/report" 
                    : "{$backendUrl}/api/discussions/{$id}/report";

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

    public function show($id)
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
                $http->withToken($token);
            }

            $response = $http->get("{$backendUrl}/api/discussions/{$id}");

            if ($response->successful()) {
                $responseBody = $response->json();
                $data = $responseBody['data'] ?? [];
                $rawDiscussion = $data['discussion'] ?? null;
                $rawComments = $data['comments'] ?? [];

                if ($rawDiscussion) {
                    $d = (array) $rawDiscussion;
                    $idVal = $d['id_diskusi'] ?? $d['id'] ?? $id;
                    $titleVal = $d['judul_diskusi'] ?? $d['title'] ?? '';
                    $bodyVal = $d['isi_diskusi'] ?? $d['body'] ?? $d['content'] ?? '';
                    $catVal = $d['kategori'] ?? $d['category'] ?? 'Umum';
                    $imgVal = $d['gambar_diskusi'] ?? $d['image_path'] ?? null;
                    $usrVal = $d['id_user'] ?? $d['user_id'] ?? null;
                    $authorNameVal = $d['nama_user'] ?? $d['author_name'] ?? 'Pengguna';
                    $authorEmailVal = $d['email_user'] ?? $d['author_email'] ?? null;

                    $discussion = array_merge($d, [
                        'id' => $idVal,
                        'id_diskusi' => $idVal,
                        'title' => $titleVal,
                        'judul_diskusi' => $titleVal,
                        'body' => $bodyVal,
                        'isi_diskusi' => $bodyVal,
                        'category' => $catVal,
                        'kategori' => $catVal,
                        'image_path' => $imgVal,
                        'gambar_diskusi' => $imgVal,
                        'user_id' => $usrVal,
                        'id_user' => $usrVal,
                        'author_name' => $authorNameVal,
                        'author_email' => $authorEmailVal,
                    ]);
                }

                $currentUserId = Auth::id();

                $commentMap = [];
                foreach ($rawComments as $comment) {
                    $c = (array) $comment;
                    $cId = $c['id_tanggapan'] ?? $c['id'] ?? null;
                    $cDiscId = $c['id_diskusi'] ?? $c['discussion_id'] ?? $id;
                    $cParentId = $c['id_parent'] ?? $c['parent_id'] ?? null;
                    $cUserId = $c['id_user'] ?? $c['user_id'] ?? null;
                    $cBody = $c['isi_tanggapan'] ?? $c['body'] ?? $c['content'] ?? '';
                    $cImage = $c['gambar_tanggapan'] ?? $c['image_path'] ?? null;
                    $cName = $c['nama_user'] ?? $c['commentator_name'] ?? 'Pengguna';
                    $cEmail = $c['email_user'] ?? $c['commentator_email'] ?? null;

                    $c['id'] = $cId;
                    $c['id_tanggapan'] = $cId;
                    $c['discussion_id'] = $cDiscId;
                    $c['id_diskusi'] = $cDiscId;
                    $c['parent_id'] = $cParentId;
                    $c['id_parent'] = $cParentId;
                    $c['user_id'] = $cUserId;
                    $c['id_user'] = $cUserId;
                    $c['body'] = $cBody;
                    $c['isi_tanggapan'] = $cBody;
                    $c['content'] = $cBody;
                    $c['image_path'] = $cImage;
                    $c['gambar_tanggapan'] = $cImage;
                    $c['commentator_name'] = $cName;
                    $c['commentator_email'] = $cEmail;
                    $c['replies'] = []; 
                    
                    $commentMap[$cId] = $c;
                }

                foreach ($commentMap as $cid => $c) {
                    $parentId = $c['parent_id'] ?? null;
                    if ($parentId && isset($commentMap[$parentId])) {
                        $commentMap[$parentId]['replies'][] = &$commentMap[$cid];
                    }
                }

                $rootComments = [];
                foreach ($commentMap as $cid => $c) {
                    if (empty($c['parent_id'])) {
                        $rootComments[] = $c;
                    }
                }

                if ($currentUserId && !empty($rootComments)) {
                    usort($rootComments, function ($a, $b) use ($currentUserId) {
                        $aIsMine = (($a['user_id'] ?? 0) == $currentUserId);
                        $bIsMine = (($b['user_id'] ?? 0) == $currentUserId);

                        $aHasMyReply = collect($a['replies'])->contains('user_id', $currentUserId);
                        $bHasMyReply = collect($b['replies'])->contains('user_id', $currentUserId);

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

    public function reply(Request $request, $id)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('warning', 'Silakan masuk terlebih dahulu untuk membalas diskusi.');
        }

        $request->validate([
            'content'   => 'required|string|max:1000',
            'image'     => 'nullable|image|mimes:jpeg,png,jpg|max:5120', 
            'parent_id' => 'nullable|integer', 
        ]);

        $token = $this->getBackendToken();
        if ($token) {
            try {
                $rawBackendUrl = config('services.medigo.url', env('MEDIGO_BE', 'http://127.0.0.1:8005'));
                $backendUrl = rtrim(str_replace('localhost', '127.0.0.1', $rawBackendUrl), '/');
                $http = Http::timeout(10)->withToken($token);

                $payload = [
                    'body'      => $request->input('content'),
                    'parent_id' => $request->input('parent_id'), 
                ];

                if ($request->hasFile('image')) {
                    $image = $request->file('image');
                    $response = $http->attach(
                        'image', 
                        file_get_contents($image->getPathname()), 
                        $image->getClientOriginalName()
                    )->post("{$backendUrl}/api/discussions/{$id}/comments", $payload);
                } else {
                    $response = $http->post("{$backendUrl}/api/discussions/{$id}/comments", $payload);
                }
                
                if ($response->successful()) {
                    return redirect()->route('discussions.show', $id)->with('success', 'Balasan Anda berhasil dikirim!');
                }
            } catch (\Exception $e) {
                Log::warning('Failed posting discussion reply to backend: ' . $e->getMessage());
            }
        }

        return redirect()->route('discussions.show', $id)->with('error', 'Gagal mengirim balasan.');
    }

    // FUNGSI: Menghapus komentar/balasan diskusi
    public function destroyComment(Request $request, $id)
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

                $response = Http::withToken($token)->delete("{$backendUrl}/api/discussion-comments/{$id}");

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

    // FUNGSI: Menghapus diskusi milik user sendiri
    public function destroyDiscussion(Request $request, $id)
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

                $response = Http::withToken($token)->delete("{$backendUrl}/api/discussions/{$id}");

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