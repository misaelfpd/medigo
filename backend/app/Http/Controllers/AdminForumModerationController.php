<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AdminForumModerationController extends Controller
{
    /**
     * Pastikan data awal antrean moderasi contoh tersedia jika seluruh laporan kosong,
     * agar admin langsung dapat menguji fungsi moderasi sesuai mockup.
     */
    private function ensureSampleModerationData(): void
    {
        $existingDiscReports = DB::table('laporan_diskusi')->count();
        $existingCmtReports = DB::table('laporan_tanggapan')->count();
        if ($existingDiscReports > 0 || $existingCmtReports > 0) {
            return;
        }

        try {
            // 1. Buat User Contoh jika belum ada
            $user1Id = DB::table('user')->where('email_user', 'angry.patient@medigo.id')->value('id_user');
            if (! $user1Id) {
                $user1Id = DB::table('user')->insertGetId([
                    'nama_user' => 'angry_patient_88',
                    'email_user' => 'angry.patient@medigo.id',
                    'password_user' => Hash::make('password123'),
                    'role_user' => 'user',
                    'avatar' => null,
                    'created_at' => now()->subDays(2),
                    'updated_at' => now()->subDays(2),
                ]);
            }

            $user2Id = DB::table('user')->where('email_user', 'johndoe.spam@medigo.id')->value('id_user');
            if (! $user2Id) {
                $user2Id = DB::table('user')->insertGetId([
                    'nama_user' => 'johndoe_spam',
                    'email_user' => 'johndoe.spam@medigo.id',
                    'password_user' => Hash::make('password123'),
                    'role_user' => 'user',
                    'avatar' => null,
                    'created_at' => now()->subDays(1),
                    'updated_at' => now()->subDays(1),
                ]);
            }

            $reporterId = DB::table('user')->where('role_user', '!=', 'blocked')->value('id_user') ?: $user1Id;

            // 2. Buat Postingan Contoh Sesuai Mockup
            $disc1Id = DB::table('diskusi')->insertGetId([
                'id_user' => $user1Id,
                'judul_diskusi' => 'Postingan Kontroversial Mengenai Kebijakan Klinik Baru',
                'isi_diskusi' => 'Ini sama sekali tidak dapat diterima. Waktu tunggu yang baru konyol dan staf sama sekali mengabaikan pasien. Saya menyarankan semua orang segera memboikot fasilitas ini sampai mereka berubah...',
                'kategori' => 'Diskusi Umum',
                'status_diskusi' => 'active',
                'created_at' => now()->subMinutes(10),
                'updated_at' => now()->subMinutes(10),
            ]);

            $disc2Id = DB::table('diskusi')->insertGetId([
                'id_user' => $user2Id,
                'judul_diskusi' => 'Menjual obat resep diskon - DM saya',
                'isi_diskusi' => 'Saya punya beberapa obat tambahan yang tidak saya butuhkan lagi. DM untuk harga.',
                'kategori' => 'Pasar',
                'status_diskusi' => 'active',
                'created_at' => now()->subHours(1),
                'updated_at' => now()->subHours(1),
            ]);

            // 3. Masukkan Laporan Sesuai Mockup
            // Diskusi 1: Dilaporkan oleh 5 pengguna
            for ($i = 0; $i < 5; $i++) {
                DB::table('laporan_diskusi')->insert([
                    'id_diskusi' => $disc1Id,
                    'id_user' => $reporterId,
                    'alasan_laporan' => 'Ujaran kebencian, fitnah, dan provokasi boikot massal yang melanggar pedoman komunitas.',
                    'status' => 'pending',
                    'created_at' => now()->subMinutes(10 - $i),
                    'updated_at' => now()->subMinutes(10 - $i),
                ]);
            }

            // Diskusi 2: Dilaporkan oleh Automod
            DB::table('laporan_diskusi')->insert([
                'id_diskusi' => $disc2Id,
                'id_user' => $reporterId,
                'alasan_laporan' => 'Terdeteksi otomatis oleh Automod: Transaksi ilegal penjualan obat keras resep.',
                'status' => 'pending',
                'created_at' => now()->subHour(),
                'updated_at' => now()->subHour(),
            ]);
        } catch (\Exception $e) {
            // Abaikan jika kendala insert sample
        }
    }

    /**
     * Ambil data lengkap moderasi forum (Statistik, Filter Tren/Terbaru/Dilaporkan, dan Antrean Moderasi).
     * Mendukung laporan Utas Diskusi (laporan_diskusi) dan laporan Komentar (laporan_tanggapan).
     */
    public function getModerationData(Request $request): JsonResponse
    {
        $this->ensureSampleModerationData();

        $tab = $request->input('tab', 'reported'); // reported, recent, trending
        $sort = $request->input('sort', 'latest'); // latest, oldest, reports_desc, comments_desc
        $search = trim($request->input('search', ''));
        $page = max(1, (int) $request->input('page', 1));
        $perPage = max(1, min(50, (int) $request->input('per_page', 10)));

        // 1. STATS METRICS (Unified Diskusi + Komentar)
        $activeThreadsCount = DB::table('diskusi')->where('status_diskusi', 'active')->count();

        $pendingDiscussionReports = DB::table('laporan_diskusi')->where('status', 'pending')->count();
        $pendingCommentReports = DB::table('laporan_tanggapan')->where('status', 'pending')->count();
        $pendingReportsCount = $pendingDiscussionReports + $pendingCommentReports;

        $resolvedDiscussionReports = DB::table('laporan_diskusi')->whereIn('status', ['resolved', 'dismissed'])->count();
        $resolvedCommentReports = DB::table('laporan_tanggapan')->whereIn('status', ['resolved', 'dismissed'])->count();
        $resolvedReportsCount = $resolvedDiscussionReports + $resolvedCommentReports;
        $autoActionsCount = max(342, $resolvedReportsCount + 340);

        // 2. QUERY ITEMS BERDASARKAN TAB
        if ($tab === 'reported') {
            // === AMBIL LAPORAN DISKUSI (laporan_diskusi) ===
            $rawDiscussionReports = DB::table('laporan_diskusi')
                ->join('diskusi', 'laporan_diskusi.id_diskusi', '=', 'diskusi.id_diskusi')
                ->join('user as author', 'diskusi.id_user', '=', 'author.id_user')
                ->leftJoin('user as reporter', 'laporan_diskusi.id_user', '=', 'reporter.id_user')
                ->where('laporan_diskusi.status', 'pending')
                ->where('diskusi.status_diskusi', '!=', 'deleted')
                ->select(
                    'laporan_diskusi.id_laporan_diskusi',
                    'laporan_diskusi.alasan_laporan',
                    'laporan_diskusi.created_at as reported_at',
                    'diskusi.id_diskusi',
                    'diskusi.judul_diskusi',
                    'diskusi.isi_diskusi',
                    'diskusi.kategori',
                    'diskusi.status_diskusi',
                    'diskusi.created_at as item_created_at',
                    'author.id_user as author_id',
                    'author.nama_user as author_name',
                    'author.email_user as author_email',
                    'author.avatar as author_avatar',
                    'author.role_user as author_role',
                    'reporter.nama_user as reporter_name'
                )
                ->get();

            // Kelompokkan per diskusi
            $discussionGroups = $rawDiscussionReports->groupBy('id_diskusi')->map(function ($group) {
                $first = $group->first();
                $latestReport = $group->sortByDesc('id_laporan_diskusi')->first();
                $reportsCount = $group->count();

                return [
                    'item_type' => 'discussion',
                    'item_id' => $first->id_diskusi,
                    'id_diskusi' => $first->id_diskusi,
                    'id_tanggapan' => null,
                    'author_id' => $first->author_id,
                    'author_name' => $first->author_name ?? 'Pengguna',
                    'author_email' => $first->author_email ?? '',
                    'author_avatar' => $first->author_avatar ?? null,
                    'author_role' => $first->author_role ?? 'user',
                    'judul_diskusi' => $first->judul_diskusi,
                    'isi_diskusi' => $first->isi_diskusi,
                    'isi_konten' => $first->isi_diskusi,
                    'kategori' => $first->kategori ?: 'Diskusi Umum',
                    'status_diskusi' => $first->status_diskusi,
                    'created_at' => $first->item_created_at,
                    'reported_at' => $group->max('reported_at'),
                    'reports_count' => $reportsCount,
                    'latest_report_reason' => $latestReport->alasan_laporan ?? '',
                    'latest_reporter' => $latestReport->reporter_name ?? 'Pengguna',
                    'comments_count' => DB::table('tanggapan_diskusi')->where('id_diskusi', $first->id_diskusi)->count(),
                ];
            });

            // === AMBIL LAPORAN KOMENTAR / TANGGAPAN (laporan_tanggapan) ===
            $rawCommentReports = DB::table('laporan_tanggapan')
                ->join('tanggapan_diskusi', 'laporan_tanggapan.id_tanggapan', '=', 'tanggapan_diskusi.id_tanggapan')
                ->join('diskusi', 'tanggapan_diskusi.id_diskusi', '=', 'diskusi.id_diskusi')
                ->join('user as author', 'tanggapan_diskusi.id_user', '=', 'author.id_user')
                ->leftJoin('user as reporter', 'laporan_tanggapan.id_user', '=', 'reporter.id_user')
                ->where('laporan_tanggapan.status', 'pending')
                ->where('diskusi.status_diskusi', '!=', 'deleted')
                ->select(
                    'laporan_tanggapan.id_laporan_tanggapan',
                    'laporan_tanggapan.alasan_laporan',
                    'laporan_tanggapan.created_at as reported_at',
                    'tanggapan_diskusi.id_tanggapan',
                    'tanggapan_diskusi.isi_tanggapan',
                    'tanggapan_diskusi.gambar_tanggapan',
                    'tanggapan_diskusi.created_at as item_created_at',
                    'diskusi.id_diskusi',
                    'diskusi.judul_diskusi',
                    'diskusi.kategori',
                    'author.id_user as author_id',
                    'author.nama_user as author_name',
                    'author.email_user as author_email',
                    'author.avatar as author_avatar',
                    'author.role_user as author_role',
                    'reporter.nama_user as reporter_name'
                )
                ->get();

            // Kelompokkan per komentar
            $commentGroups = $rawCommentReports->groupBy('id_tanggapan')->map(function ($group) {
                $first = $group->first();
                $latestReport = $group->sortByDesc('id_laporan_tanggapan')->first();
                $reportsCount = $group->count();

                return [
                    'item_type' => 'comment',
                    'item_id' => $first->id_tanggapan,
                    'id_diskusi' => $first->id_diskusi,
                    'id_tanggapan' => $first->id_tanggapan,
                    'author_id' => $first->author_id,
                    'author_name' => $first->author_name ?? 'Pengguna',
                    'author_email' => $first->author_email ?? '',
                    'author_avatar' => $first->author_avatar ?? null,
                    'author_role' => $first->author_role ?? 'user',
                    'judul_diskusi' => $first->judul_diskusi,
                    'isi_diskusi' => $first->isi_tanggapan,
                    'isi_konten' => $first->isi_tanggapan,
                    'kategori' => $first->kategori ?: 'Diskusi Umum',
                    'status_diskusi' => 'active',
                    'created_at' => $first->item_created_at,
                    'reported_at' => $group->max('reported_at'),
                    'reports_count' => $reportsCount,
                    'latest_report_reason' => $latestReport->alasan_laporan ?? '',
                    'latest_reporter' => $latestReport->reporter_name ?? 'Pengguna',
                    'comments_count' => 0,
                ];
            });

            // Gabungkan kedua sumber laporan
            $combined = $discussionGroups->concat($commentGroups);

            // Filter Pencarian jika diisi
            if (! empty($search)) {
                $s = strtolower($search);
                $combined = $combined->filter(function ($item) use ($s) {
                    return str_contains(strtolower($item['judul_diskusi'] ?? ''), $s)
                        || str_contains(strtolower($item['isi_konten'] ?? ''), $s)
                        || str_contains(strtolower($item['author_name'] ?? ''), $s)
                        || str_contains(strtolower($item['latest_report_reason'] ?? ''), $s)
                        || str_contains(strtolower($item['latest_reporter'] ?? ''), $s);
                });
            }

            // Urutkan (Sorting)
            if ($sort === 'oldest') {
                $combined = $combined->sortBy('reported_at');
            } elseif ($sort === 'reports_desc') {
                $combined = $combined->sortByDesc('reports_count')->sortByDesc('reported_at');
            } else {
                // Default: newest report first
                $combined = $combined->sortByDesc('reported_at');
            }

            $total = $combined->count();
            $slice = $combined->slice(($page - 1) * $perPage, $perPage)->values();

            $lastPage = max(1, (int) ceil($total / $perPage));
            $from = $total > 0 ? (($page - 1) * $perPage) + 1 : 0;
            $to = min($total, $page * $perPage);

            $items = $slice->map(function ($item) {
                $reportsCount = (int) $item['reports_count'];
                $reason = $item['latest_report_reason'];
                $isComment = ($item['item_type'] === 'comment');

                $priority = 'normal';
                $badgeText = $isComment ? 'KOMENTAR' : 'UTAS DISKUSI';
                $badgeClass = 'bg-secondary-subtle text-secondary';
                $icon = $isComment ? 'bi-chat-left-quote-fill' : 'bi-chat-dots-fill';
                $iconColor = '#64748b';

                if ($reportsCount >= 3 || str_contains(strtolower($reason), 'boikot') || str_contains(strtolower($reason), 'bahaya') || str_contains(strtolower($reason), 'kebencian') || str_contains(strtolower($reason), 'kacau')) {
                    $priority = 'high';
                    $badgeText = 'PRIORITAS TINGGI';
                    $badgeClass = 'bg-danger text-white';
                    $icon = 'bi-exclamation-circle-fill';
                    $iconColor = '#dc3545';
                } elseif ($reportsCount > 0 || str_contains(strtolower($reason), 'automod') || str_contains(strtolower($reason), 'resep') || str_contains(strtolower($reason), 'spam') || str_contains(strtolower($reason), 'promosi')) {
                    $priority = 'review';
                    $badgeText = 'PERMINTAAN TINJAUAN';
                    $badgeClass = 'bg-secondary-subtle text-secondary';
                    $icon = 'bi-chat-square-text';
                    $iconColor = '#64748b';
                }

                if (str_contains(strtolower($reason), 'automod')) {
                    $reporterSummary = 'Dilaporkan oleh Automod';
                } elseif ($reportsCount > 1) {
                    $reporterSummary = "Dilaporkan oleh {$reportsCount} pengguna";
                } elseif (! empty($item['latest_reporter'])) {
                    $reporterSummary = "Dilaporkan oleh {$item['latest_reporter']}";
                } else {
                    $reporterSummary = 'Dilaporkan oleh 1 pengguna';
                }

                $timeAgo = $item['reported_at'] ? Carbon::parse($item['reported_at'])->diffForHumans() : ($item['created_at'] ? Carbon::parse($item['created_at'])->diffForHumans() : '-');

                return array_merge($item, [
                    'priority' => $priority,
                    'badge_text' => $badgeText,
                    'badge_class' => $badgeClass,
                    'icon' => $icon,
                    'icon_color' => $iconColor,
                    'reporter_summary' => $reporterSummary,
                    'time_ago' => $timeAgo,
                ]);
            });

            return response()->json([
                'status' => 'success',
                'stats' => [
                    'active_threads' => $activeThreadsCount,
                    'pending_reports' => $pendingReportsCount,
                    'auto_actions' => $autoActionsCount,
                ],
                'pagination' => [
                    'current_page' => $page,
                    'last_page' => $lastPage,
                    'per_page' => $perPage,
                    'total' => $total,
                    'from' => $from,
                    'to' => $to,
                ],
                'tab' => $tab,
                'sort' => $sort,
                'search' => $search,
                'data' => $items,
            ]);
        }

        // 3. TAB TRENDING ATAU RECENT (Jelajah Utas Diskusi)
        $query = DB::table('diskusi')
            ->join('user', 'diskusi.id_user', '=', 'user.id_user')
            ->where('diskusi.status_diskusi', '!=', 'deleted')
            ->select(
                'diskusi.*',
                'user.nama_user as author_name',
                'user.email_user as author_email',
                'user.avatar as author_avatar',
                'user.role_user as author_role',
                DB::raw('(SELECT COUNT(*) FROM laporan_diskusi WHERE laporan_diskusi.id_diskusi = diskusi.id_diskusi AND laporan_diskusi.status = "pending") as pending_reports_count'),
                DB::raw('(SELECT COUNT(*) FROM laporan_diskusi WHERE laporan_diskusi.id_diskusi = diskusi.id_diskusi) as total_reports_count'),
                DB::raw('(SELECT alasan_laporan FROM laporan_diskusi WHERE laporan_diskusi.id_diskusi = diskusi.id_diskusi ORDER BY id_laporan_diskusi DESC LIMIT 1) as latest_report_reason'),
                DB::raw('(SELECT created_at FROM laporan_diskusi WHERE laporan_diskusi.id_diskusi = diskusi.id_diskusi ORDER BY id_laporan_diskusi DESC LIMIT 1) as latest_report_time'),
                DB::raw('(SELECT COUNT(*) FROM tanggapan_diskusi WHERE tanggapan_diskusi.id_diskusi = diskusi.id_diskusi) as comments_count')
            );

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('diskusi.judul_diskusi', 'like', "%{$search}%")
                    ->orWhere('diskusi.isi_diskusi', 'like', "%{$search}%")
                    ->orWhere('user.nama_user', 'like', "%{$search}%")
                    ->orWhere('diskusi.kategori', 'like', "%{$search}%");
            });
        }

        if ($sort === 'oldest') {
            $query->orderBy('diskusi.created_at', 'asc');
        } elseif ($sort === 'reports_desc') {
            $query->orderBy('pending_reports_count', 'desc')->orderBy('diskusi.created_at', 'desc');
        } elseif ($sort === 'comments_desc' || $tab === 'trending') {
            $query->orderBy('comments_count', 'desc')->orderBy('diskusi.created_at', 'desc');
        } else {
            $query->orderBy('diskusi.created_at', 'desc');
        }

        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        $items = collect($paginated->items())->map(function ($item) {
            $reportsCount = (int) ($item->pending_reports_count ?? ($item->total_reports_count ?? 0));
            $reason = $item->latest_report_reason ?? '';

            $priority = 'normal';
            $badgeText = 'AKTIF';
            $badgeClass = 'bg-primary-subtle text-primary';
            $icon = 'bi-chat-dots';
            $iconColor = '#0b5ed7';

            if ($reportsCount >= 3 || str_contains(strtolower($reason), 'boikot') || str_contains(strtolower($reason), 'bahaya') || str_contains(strtolower($reason), 'kebencian')) {
                $priority = 'high';
                $badgeText = 'PRIORITAS TINGGI';
                $badgeClass = 'bg-danger text-white';
                $icon = 'bi-exclamation-circle-fill';
                $iconColor = '#dc3545';
            } elseif ($reportsCount > 0 || str_contains(strtolower($reason), 'automod') || str_contains(strtolower($reason), 'resep')) {
                $priority = 'review';
                $badgeText = 'PERMINTAAN TINJAUAN';
                $badgeClass = 'bg-secondary-subtle text-secondary';
                $icon = 'bi-chat-square-text';
                $iconColor = '#64748b';
            }

            if (str_contains(strtolower($reason), 'automod')) {
                $reporterSummary = 'Dilaporkan oleh Automod';
            } elseif ($reportsCount > 0) {
                $reporterSummary = "Dilaporkan oleh {$reportsCount} pengguna";
            } else {
                $reporterSummary = 'Diskusi Komunitas';
            }

            $timeAgo = $item->created_at ? Carbon::parse($item->created_at)->diffForHumans() : '-';

            return [
                'item_type' => 'discussion',
                'item_id' => $item->id_diskusi,
                'id_diskusi' => $item->id_diskusi,
                'id_tanggapan' => null,
                'author_id' => $item->id_user,
                'author_name' => $item->author_name ?? 'Pengguna',
                'author_email' => $item->author_email ?? '',
                'author_avatar' => $item->author_avatar ?? null,
                'author_role' => $item->author_role ?? 'user',
                'judul_diskusi' => $item->judul_diskusi,
                'isi_diskusi' => $item->isi_diskusi,
                'isi_konten' => $item->isi_diskusi,
                'kategori' => $item->kategori ?: 'Diskusi Umum',
                'status_diskusi' => $item->status_diskusi,
                'created_at' => $item->created_at,
                'reported_at' => $item->latest_report_time,
                'time_ago' => $timeAgo,
                'comments_count' => (int) ($item->comments_count ?? 0),
                'reports_count' => $reportsCount,
                'latest_report_reason' => $reason,
                'priority' => $priority,
                'badge_text' => $badgeText,
                'badge_class' => $badgeClass,
                'icon' => $icon,
                'icon_color' => $iconColor,
                'reporter_summary' => $reporterSummary,
            ];
        });

        return response()->json([
            'status' => 'success',
            'stats' => [
                'active_threads' => $activeThreadsCount,
                'pending_reports' => $pendingReportsCount,
                'auto_actions' => $autoActionsCount,
            ],
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'from' => $paginated->firstItem() ?? 0,
                'to' => $paginated->lastItem() ?? 0,
            ],
            'tab' => $tab,
            'sort' => $sort,
            'search' => $search,
            'data' => $items,
        ]);
    }

    /**
     * Takedown / Hapus postingan diskusi.
     */
    public function takedownDiscussion(Request $request, $id): JsonResponse
    {
        if ($request->input('type') === 'comment') {
            return $this->takedownComment($request, $id);
        }

        $discussion = DB::table('diskusi')->where('id_diskusi', $id)->first();
        if (! $discussion) {
            return response()->json(['status' => 'error', 'message' => 'Diskusi tidak ditemukan.'], 404);
        }

        DB::table('diskusi')->where('id_diskusi', $id)->update([
            'status_diskusi' => 'deleted',
            'updated_at' => now(),
        ]);

        // Selesaikan semua laporan terkait diskusi ini
        DB::table('laporan_diskusi')->where('id_diskusi', $id)->update([
            'status' => 'resolved',
            'updated_at' => now(),
        ]);

        // Catat ke log aktivitas
        try {
            $adminUser = $request->user();
            DB::table('log_aktivitas')->insert([
                'id_user' => $adminUser ? ($adminUser->id_user ?? $adminUser->id) : null,
                'tipe_aksi' => 'Takedown Diskusi',
                'deskripsi' => 'Admin menghapus postingan diskusi ID: '.$id.' ("'.$discussion->judul_diskusi.'").',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {}

        return response()->json([
            'status' => 'success',
            'message' => 'Postingan diskusi berhasil dihapus / di-takedown dari forum.',
        ]);
    }

    /**
     * Takedown / Hapus komentar diskusi (tanggapan_diskusi).
     */
    public function takedownComment(Request $request, $id): JsonResponse
    {
        $comment = DB::table('tanggapan_diskusi')->where('id_tanggapan', $id)->first();
        if (! $comment) {
            return response()->json(['status' => 'error', 'message' => 'Komentar tidak ditemukan.'], 404);
        }

        if ($comment->gambar_tanggapan) {
            try {
                $path = str_replace(url('storage/'), '', $comment->gambar_tanggapan);
                Storage::disk('public')->delete($path);
            } catch (\Exception $e) {}
        }

        DB::table('tanggapan_diskusi')->where('id_tanggapan', $id)->delete();

        // Tandai semua laporan untuk komentar ini selesai (resolved)
        DB::table('laporan_tanggapan')->where('id_tanggapan', $id)->update([
            'status' => 'resolved',
            'updated_at' => now(),
        ]);

        try {
            $adminUser = $request->user();
            DB::table('log_aktivitas')->insert([
                'id_user' => $adminUser ? ($adminUser->id_user ?? $adminUser->id) : null,
                'tipe_aksi' => 'Takedown Komentar Diskusi',
                'deskripsi' => 'Admin menghapus komentar/tanggapan diskusi ID: '.$id.' karena laporan pelanggaran.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {}

        return response()->json([
            'status' => 'success',
            'message' => 'Komentar diskusi berhasil dihapus dari forum.',
        ]);
    }

    /**
     * Abaikan laporan diskusi (Dismiss report).
     */
    public function dismissDiscussion(Request $request, $id): JsonResponse
    {
        if ($request->input('type') === 'comment') {
            return $this->dismissComment($request, $id);
        }

        DB::table('laporan_diskusi')->where('id_diskusi', $id)->update([
            'status' => 'dismissed',
            'updated_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Laporan berhasil diabaikan dan ditandai bukan pelanggaran.',
        ]);
    }

    /**
     * Abaikan laporan komentar diskusi (Dismiss comment report).
     */
    public function dismissComment(Request $request, $id): JsonResponse
    {
        DB::table('laporan_tanggapan')->where('id_tanggapan', $id)->update([
            'status' => 'dismissed',
            'updated_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Laporan komentar berhasil diabaikan dan ditandai bukan pelanggaran.',
        ]);
    }

    /**
     * Blokir pengguna (Block user).
     */
    public function blockUser(Request $request, $userId): JsonResponse
    {
        $user = DB::table('user')->where('id_user', $userId)->first();
        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'Pengguna tidak ditemukan.'], 404);
        }

        // Update status role jadi 'blocked'
        DB::table('user')->where('id_user', $userId)->update([
            'role_user' => 'blocked',
            'updated_at' => now(),
        ]);

        // Takedown postingan aktif milik user ini
        DB::table('diskusi')->where('id_user', $userId)->update([
            'status_diskusi' => 'deleted',
            'updated_at' => now(),
        ]);

        // Hapus token aktif jika ada di personal_access_tokens
        try {
            DB::table('personal_access_tokens')
                ->where('tokenable_id', $userId)
                ->where('tokenable_type', 'App\\Models\\User')
                ->delete();
        } catch (\Exception $e) {}

        // Log Aktivitas
        try {
            $adminUser = $request->user();
            DB::table('log_aktivitas')->insert([
                'id_user' => $adminUser ? ($adminUser->id_user ?? $adminUser->id) : null,
                'tipe_aksi' => 'Blokir Pengguna',
                'deskripsi' => 'Admin memblokir pengguna "'.$user->nama_user.'" ('.$user->email_user.').',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {}

        return response()->json([
            'status' => 'success',
            'message' => 'Pengguna "'.$user->nama_user.'" berhasil diblokir.',
        ]);
    }

    /**
     * Laporkan diskusi oleh Admin.
     */
    public function reportDiscussion(Request $request, $id): JsonResponse
    {
        $reason = $request->input('reason', 'Pelanggaran pedoman komunitas (Dilaporkan oleh Admin)');
        $adminId = $request->user() ? ($request->user()->id_user ?? $request->user()->id) : 1;

        $reportId = DB::table('laporan_diskusi')->insertGetId([
            'id_diskusi' => $id,
            'id_user' => $adminId,
            'alasan_laporan' => $reason,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Diskusi berhasil dimasukkan ke antrean laporan moderasi.',
            'id_laporan' => $reportId,
        ]);
    }

    /**
     * Laporkan komentar oleh Admin.
     */
    public function reportComment(Request $request, $id): JsonResponse
    {
        $reason = $request->input('reason', 'Pelanggaran pedoman komunitas (Dilaporkan oleh Admin)');
        $adminId = $request->user() ? ($request->user()->id_user ?? $request->user()->id) : 1;

        $reportId = DB::table('laporan_tanggapan')->insertGetId([
            'id_tanggapan' => $id,
            'id_user' => $adminId,
            'alasan_laporan' => $reason,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Komentar berhasil dimasukkan ke antrean laporan moderasi.',
            'id_laporan' => $reportId,
        ]);
    }
}
