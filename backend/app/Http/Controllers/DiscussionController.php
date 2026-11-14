<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DiscussionController extends Controller
{
    // 1. Tampilkan semua diskusi (diurutkan dari yang paling baru)
    public function index(Request $request)
    {
        // Rute publik: baca token kalau ada, untuk mengisi liked_by_me
        $viewer = $request->user('sanctum');
        $viewerId = $viewer ? (int) $viewer->id_user : 0;

        // 1. Siapkan kerangka query dasar dengan penghitungan real comments_count dan email author
        $query = DB::table('diskusi')
            ->join('user', 'diskusi.id_user', '=', 'user.id_user')
            ->where('diskusi.status_diskusi', 'active')
            ->select(
                'diskusi.*',
                'user.nama_user as author_name',
                'user.email_user as author_email',
                'user.avatar as author_avatar',
                DB::raw('(SELECT COUNT(*) FROM tanggapan_diskusi WHERE tanggapan_diskusi.id_diskusi = diskusi.id_diskusi) as comments_count'),
                DB::raw('(SELECT COUNT(*) FROM like_diskusi WHERE like_diskusi.id_diskusi = diskusi.id_diskusi) as likes_count')
            )
            ->selectRaw('(SELECT COUNT(*) FROM like_diskusi WHERE like_diskusi.id_diskusi = diskusi.id_diskusi AND like_diskusi.id_user = ?) as liked_by_me', [$viewerId]);

        // Urutan: terbaru (default) atau terpopuler (like terbanyak)
        if ($request->query('sort') === 'terpopuler') {
            $query->orderBy('likes_count', 'desc');
        }
        $query->orderBy('diskusi.created_at', 'desc');

        // Filter diskusi yang me-mention faskes tertentu
        if ($request->filled('faskes_id')) {
            $query->where('diskusi.id_faskes_mention', (int) $request->query('faskes_id'));
        }

        // 2. Cek apakah ada request filter kategori
        if ($request->has('category') && ! empty($request->query('category'))) {
            $cat = trim($request->query('category'));
            if ($cat === 'BPJS') {
                $query->whereIn('diskusi.kategori', ['BPJS', 'BPJS & Asuransi', 'Tanya BPJS']);
            } elseif ($cat === 'Rekomendasi RS' || $cat === 'Rekomendasi') {
                $query->whereIn('diskusi.kategori', ['Rekomendasi RS', 'Rekomendasi', 'Rekomendasi Faskes']);
            } elseif ($cat === 'Tips Kesehatan') {
                $query->whereIn('diskusi.kategori', ['Tips Kesehatan', 'Tips & Informasi', 'Gaya Hidup & Nutrisi']);
            } elseif ($cat === 'Umum') {
                $query->whereIn('diskusi.kategori', ['Umum', 'Kesehatan Umum']);
            } elseif ($cat === 'Ulasan Rumah Sakit') {
                $query->whereIn('diskusi.kategori', ['Ulasan Rumah Sakit', 'Ulasan RS', 'Review RS', 'Review Faskes']);
            } else {
                $query->where('diskusi.kategori', $cat);
            }
        }

        // 3. Eksekusi pencarian dengan paginasi
        $discussions = $query->paginate(10);

        $mentions = self::mentionedFaskesMap($discussions->getCollection()->pluck('id_faskes_mention')->all());
        $discussions->getCollection()->transform(function ($item) use ($mentions) {
            $item->likes_count = (int) $item->likes_count;
            $item->liked_by_me = (int) $item->liked_by_me > 0;
            $item->mentioned_faskes = $mentions[$item->id_faskes_mention] ?? null;
            // Thumbnail kartu diskusi = foto pertama yang diunggah di diskusi
            $item->images_list = self::imagesList($item->images ?? null, $item->gambar_diskusi ?? null);
            $item->thumbnail_url = $item->images_list[0] ?? null;

            return $item;
        });

        return response()->json([
            'message' => 'Berhasil mengambil daftar diskusi',
            'data' => $discussions,
        ]);
    }

    // 2. Bikin topik diskusi baru dengan lampiran gambar (hingga 3 foto)
    public function store(Request $request)
    {
        if (! $request->filled('faskes_id')) {
            $alias = $request->input('mentioned_faskes_id', $request->input('id_faskes_mention'));
            if ($alias !== null && $alias !== '') {
                $request->merge(['faskes_id' => $alias]);
            }
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category' => 'nullable|string',
            'images' => 'nullable|array|max:3',
            'images.*' => 'image|max:5120',
            'image' => 'nullable|image|max:5120',
            // Faskes yang di-mention di judul (maksimal 1). Nama field lain diterima sebagai alias.
            'faskes_id' => 'nullable|integer|exists:faskes,id_faskes',
        ], [
            'faskes_id.integer' => 'Hanya boleh me-mention 1 faskes di judul diskusi.',
            'faskes_id.exists' => 'Faskes yang di-mention tidak ditemukan.',
        ]);

        $user_id = $request->user()->id_user ?? $request->user()->id;

        $imageUrls = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                if ($file && $file->isValid()) {
                    $path = $file->store('discussions', 'public');
                    $imageUrls[] = url('storage/'.$path);
                }
            }
        } elseif ($request->hasFile('image')) {
            $file = $request->file('image');
            if ($file && $file->isValid()) {
                $path = $file->store('discussions', 'public');
                $imageUrls[] = url('storage/'.$path);
            }
        }

        $firstImage = ! empty($imageUrls) ? $imageUrls[0] : null;
        $imagesJson = ! empty($imageUrls) ? json_encode($imageUrls) : null;

        $discussionId = DB::table('diskusi')->insertGetId([
            'id_user' => $user_id,
            'judul_diskusi' => $request->input('title'),
            'isi_diskusi' => $request->input('content'),
            'kategori' => $request->input('category', 'Umum'),
            'id_faskes_mention' => $request->filled('faskes_id') ? (int) $request->input('faskes_id') : null,
            'gambar_diskusi' => $firstImage,
            'images' => $imagesJson,
            'status_diskusi' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Catat ke Log Aktivitas
        DB::table('log_aktivitas')->insert([
            'id_user' => $user_id,
            'tipe_aksi' => 'Buat Diskusi',
            'deskripsi' => 'Membuat diskusi baru dengan judul: '.$request->input('title'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $mentioned = $request->filled('faskes_id')
            ? (self::mentionedFaskesMap([(int) $request->input('faskes_id')])[(int) $request->input('faskes_id')] ?? null)
            : null;

        return response()->json([
            'message' => 'Diskusi berhasil dibuat',
            'discussion_id' => $discussionId,
            'mentioned_faskes' => $mentioned,
        ], 201);
    }

    public function show(Request $request, $id)
    {
        // Rute ini publik (di luar auth:sanctum), jadi guard sanctum harus disebut eksplisit
        // agar Bearer token terbaca. Tanpa ini logged_in_user_id selalu null.
        $currentUser = $request->user('sanctum');
        $currentUserId = $currentUser ? ($currentUser->id_user ?? $currentUser->id) : null;

        $discussion = DB::table('diskusi')
            ->join('user', 'diskusi.id_user', '=', 'user.id_user')
            ->where('diskusi.id_diskusi', $id)
            ->where('diskusi.status_diskusi', 'active')
            ->select(
                'diskusi.id_diskusi as id',
                'diskusi.id_user as user_id',
                'diskusi.judul_diskusi as title',
                'diskusi.isi_diskusi as body',
                'diskusi.kategori as category',
                'diskusi.gambar_diskusi as image_path',
                'diskusi.images',
                'diskusi.status_diskusi as status',
                'diskusi.id_faskes_mention',
                'diskusi.created_at',
                'user.nama_user as author_name',
                'user.email_user as author_email',
                'user.avatar as author_avatar'
            )
            ->first();

        if (! $discussion) {
            return response()->json(['message' => 'Diskusi tidak ditemukan atau telah dihapus'], 404);
        }

        // Sediakan daftar array gambar untuk kenyamanan rendering di frontend
        $discussion->images_list = self::imagesList($discussion->images ?? null, $discussion->image_path ?? null);
        $discussion->thumbnail_url = $discussion->images_list[0] ?? null;

        $discussion->mentioned_faskes = $discussion->id_faskes_mention
            ? (self::mentionedFaskesMap([$discussion->id_faskes_mention])[$discussion->id_faskes_mention] ?? null)
            : null;
        $discussion->likes_count = DB::table('like_diskusi')->where('id_diskusi', $id)->count();
        $discussion->liked_by_me = $currentUserId
            ? DB::table('like_diskusi')->where('id_diskusi', $id)->where('id_user', $currentUserId)->exists()
            : false;

        $commentSelect = [
            'tanggapan_diskusi.id_tanggapan as id',
            'tanggapan_diskusi.id_user as user_id',
            'tanggapan_diskusi.id_diskusi as discussion_id',
            'tanggapan_diskusi.id_parent as parent_id',
            'tanggapan_diskusi.isi_tanggapan as body',
            'tanggapan_diskusi.gambar_tanggapan as image_path',
            'tanggapan_diskusi.created_at',
            'user.nama_user as commentator_name',
            'user.email_user as commentator_email',
            'user.avatar as commentator_avatar',
        ];

        $comments = DB::table('tanggapan_diskusi')
            ->join('user', 'tanggapan_diskusi.id_user', '=', 'user.id_user')
            ->where('tanggapan_diskusi.id_diskusi', $id)
            ->select($commentSelect)
            ->orderBy('tanggapan_diskusi.created_at', 'asc')
            ->get();

        // Jumlah like tiap komentar + apakah user yang sedang login sudah like
        $commentIds = $comments->pluck('id')->all();
        $likeCounts = DB::table('like_tanggapan')
            ->whereIn('id_tanggapan', $commentIds)
            ->select('id_tanggapan', DB::raw('COUNT(*) as total'))
            ->groupBy('id_tanggapan')
            ->pluck('total', 'id_tanggapan');
        $likedByMe = $currentUserId
            ? DB::table('like_tanggapan')->whereIn('id_tanggapan', $commentIds)->where('id_user', $currentUserId)->pluck('id_tanggapan')->flip()
            : collect();
        $comments->transform(function ($comment) use ($likeCounts, $likedByMe) {
            $comment->likes_count = (int) ($likeCounts[$comment->id] ?? 0);
            $comment->liked_by_me = isset($likedByMe[$comment->id]);

            return $comment;
        });

        return response()->json([
            'message' => 'Berhasil',
            'data' => [
                'discussion' => $discussion,
                'comments' => $comments,
                'logged_in_user_id' => $currentUserId,
            ],
        ]);
    }

    // 4. Mengambil riwayat diskusi milik user yang sedang login
    public function myDiscussions(Request $request)
    {
        $userId = $request->user()->id_user ?? $request->user()->id;

        $discussions = DB::table('diskusi')
            ->join('user', 'diskusi.id_user', '=', 'user.id_user')
            ->where('diskusi.id_user', $userId)
            // Diskusi yang sudah dihapus/di-takedown tidak ditampilkan lagi di profil
            ->where('diskusi.status_diskusi', '!=', 'deleted')
            ->select(
                'diskusi.*',
                'user.nama_user as author_name',
                DB::raw('(SELECT COUNT(*) FROM like_diskusi WHERE like_diskusi.id_diskusi = diskusi.id_diskusi) as likes_count')
            )
            ->orderBy('diskusi.created_at', 'desc')
            ->get();

        $mentions = self::mentionedFaskesMap($discussions->pluck('id_faskes_mention')->all());
        $discussions->transform(function ($item) use ($mentions) {
            $item->likes_count = (int) $item->likes_count;
            $item->mentioned_faskes = $mentions[$item->id_faskes_mention] ?? null;
            $item->images_list = self::imagesList($item->images ?? null, $item->gambar_diskusi ?? null);
            $item->thumbnail_url = $item->images_list[0] ?? null;

            return $item;
        });

        return response()->json([
            'message' => 'Berhasil mengambil daftar diskusi saya',
            'data' => $discussions,
        ]);
    }

    // 6. Mengambil riwayat tanggapan/komentar milik user yang sedang login
    public function myComments(Request $request)
    {
        $userId = $request->user()->id_user ?? $request->user()->id;

        $comments = DB::table('tanggapan_diskusi')
            ->join('diskusi', 'tanggapan_diskusi.id_diskusi', '=', 'diskusi.id_diskusi')
            ->where('tanggapan_diskusi.id_user', $userId)
            ->where('diskusi.status_diskusi', '!=', 'deleted')
            ->select(
                'tanggapan_diskusi.*',
                'diskusi.judul_diskusi as discussion_title'
            )
            ->orderBy('tanggapan_diskusi.created_at', 'desc')
            ->get();

        return response()->json([
            'message' => 'Berhasil mengambil daftar tanggapan saya',
            'data' => $comments,
        ]);
    }

    // 7. Hapus diskusi milik user sendiri
    public function destroy(Request $request, $id)
    {
        $userId = $request->user()->id_user ?? $request->user()->id;

        $discussion = DB::table('diskusi')->where('id_diskusi', $id)->first();

        if (! $discussion) {
            return response()->json(['status' => 'error', 'message' => 'Diskusi tidak ditemukan'], 404);
        }

        $userRole = $request->user()->role_user ?? $request->user()->role ?? '';
        $isAdmin = method_exists($request->user(), 'isAdmin') ? $request->user()->isAdmin() : ($userRole === 'admin');

        // Gunakan casting (int) untuk pencegahan mismatch tipe data
        if ((int) $discussion->id_user !== (int) $userId && ! $isAdmin) {
            return response()->json(['status' => 'error', 'message' => 'Anda tidak memiliki izin untuk menghapus diskusi ini'], 403);
        }

        DB::table('diskusi')->where('id_diskusi', $id)->update([
            'status_diskusi' => 'deleted',
            'updated_at' => now(),
        ]);

        return response()->json(['status' => 'success', 'message' => 'Diskusi berhasil dihapus']);
    }

    /**
     * Daftar URL foto diskusi dari kolom `images` (JSON) dengan cadangan kolom `gambar_diskusi`.
     *
     * @return array<int, string>
     */
    public static function imagesList($images, ?string $firstImage): array
    {
        $list = [];
        if (! empty($images)) {
            $decoded = is_array($images) ? $images : json_decode((string) $images, true);
            if (is_array($decoded)) {
                $list = array_values(array_filter($decoded, fn ($url) => is_string($url) && $url !== ''));
            }
        }

        if (empty($list) && ! empty($firstImage)) {
            $list = [$firstImage];
        }

        return $list;
    }

    /**
     * Ringkasan faskes yang di-mention, dikunci per id_faskes.
     *
     * @return array<int, array>
     */
    public static function mentionedFaskesMap(array $faskesIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $faskesIds))));
        if (empty($ids)) {
            return [];
        }

        $thumbnails = FotoFaskesController::thumbnailUrls($ids);

        return DB::table('faskes')
            ->whereIn('id_faskes', $ids)
            ->get(['id_faskes', 'nama_faskes', 'jenis_faskes', 'kota_kabupaten', 'kecamatan'])
            ->mapWithKeys(fn ($f) => [(int) $f->id_faskes => ForumController::faskesSummary($f, $thumbnails[$f->id_faskes] ?? null)])
            ->all();
    }
}
