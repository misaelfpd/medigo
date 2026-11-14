<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

class DiscussionReportController extends Controller
{
    /**
     * User: Report an offensive or rule-violating discussion.
     */
    public function store(Request $request, $discussionId)
    {
        $reason = $request->input('reason', $request->input('alasan_laporan'));
        $notes = $request->input('notes', $request->input('catatan_tambahan'));
        $notes = is_string($notes) && trim($notes) !== '' ? trim($notes) : null;
        $request->merge(['reason' => $reason, 'notes' => $notes]);

        $request->validate([
            'reason' => 'required|string|max:500',
            'notes' => 'nullable|string|max:500',
        ], [
            'notes.max' => 'Catatan tambahan maksimal 500 karakter.',
            'reason.required' => 'Alasan laporan diskusi wajib diisi.',
            'reason.max' => 'Alasan laporan maksimal 500 karakter.',
        ]);

        // Pastikan diskusi ada dan belum di takedown
        $discussion = DB::table('diskusi')
            ->where('id_diskusi', $discussionId)
            ->where('status_diskusi', 'active')
            ->first();

        if (! $discussion) {
            return response()->json([
                'status' => 'error',
                'message' => 'Diskusi tidak ditemukan atau sudah dihapus.',
            ], 404);
        }

        $currentUser = $request->user();
        if (! $currentUser && ($bearer = $request->bearerToken())) {
            $tokenObj = PersonalAccessToken::findToken($bearer);
            if ($tokenObj && $tokenObj->tokenable) {
                $currentUser = $tokenObj->tokenable;
            }
        }

        if (! $currentUser) {
            return response()->json([
                'status' => 'error',
                'message' => 'Silakan masuk terlebih dahulu untuk melaporkan diskusi.',
            ], 401);
        }

        $userId = $currentUser->id_user ?? $currentUser->id;

        // Prevent reporting own discussion
        if ((int) $discussion->id_user === (int) $userId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak dapat melaporkan diskusi Anda sendiri.',
            ], 422);
        }

        $alreadyReported = DB::table('laporan_diskusi')
            ->where('id_diskusi', $discussionId)
            ->where('id_user', $userId)
            ->exists();

        if ($alreadyReported) {
            return response()->json([
                'status' => 'success',
                'message' => 'Anda sudah pernah melaporkan diskusi ini sebelumnya. Laporan Anda sedang dalam antrean peninjauan admin.',
            ]);
        }

        $reportId = DB::table('laporan_diskusi')->insertGetId([
            'id_diskusi' => $discussionId,
            'id_user' => $userId,
            'alasan_laporan' => $reason,
            'catatan_tambahan' => $notes,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $report = DB::table('laporan_diskusi')->where('id_laporan_diskusi', $reportId)->first();

        return response()->json([
            'status' => 'success',
            'message' => 'Laporan diskusi berhasil dikirim dan akan segera ditinjau oleh admin.',
            'data' => $report,
        ], 201);
    }

    /**
     * Admin: List all discussion reports with reporter, author, and discussion details.
     */
    public function indexAdmin(Request $request)
    {
        $query = DB::table('laporan_diskusi')
            ->join('user as reporter', 'laporan_diskusi.id_user', '=', 'reporter.id_user')
            ->join('diskusi', 'laporan_diskusi.id_diskusi', '=', 'diskusi.id_diskusi')
            ->leftJoin('user as author', 'diskusi.id_user', '=', 'author.id_user')
            ->select(
                'laporan_diskusi.*',
                'reporter.nama_user as reporter_name',
                'reporter.email_user as reporter_email',
                'diskusi.judul_diskusi as discussion_title',
                'diskusi.isi_diskusi as discussion_content',
                'diskusi.kategori as discussion_category',
                'diskusi.status_diskusi as discussion_status',
                'diskusi.created_at as discussion_created_at',
                'author.nama_user as author_name',
                'author.email_user as author_email'
            );

        if ($request->filled('status')) {
            $query->where('laporan_diskusi.status', $request->status);
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('diskusi.judul_diskusi', 'like', "%{$s}%")
                    ->orWhere('laporan_diskusi.alasan_laporan', 'like', "%{$s}%")
                    ->orWhere('reporter.nama_user', 'like', "%{$s}%")
                    ->orWhere('author.nama_user', 'like', "%{$s}%");
            });
        }

        $reports = $query->orderBy('laporan_diskusi.created_at', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil mengambil daftar laporan diskusi',
            'total' => $reports->count(),
            'data' => $reports,
        ]);
    }

    /**
     * Admin: Detail of a single discussion report.
     */
    public function show($id)
    {
        $report = DB::table('laporan_diskusi')
            ->join('user as reporter', 'laporan_diskusi.id_user', '=', 'reporter.id_user')
            ->join('diskusi', 'laporan_diskusi.id_diskusi', '=', 'diskusi.id_diskusi')
            ->leftJoin('user as author', 'diskusi.id_user', '=', 'author.id_user')
            ->where('laporan_diskusi.id_laporan_diskusi', $id)
            ->select(
                'laporan_diskusi.*',
                'reporter.nama_user as reporter_name',
                'reporter.email_user as reporter_email',
                'diskusi.judul_diskusi as discussion_title',
                'diskusi.isi_diskusi as discussion_content',
                'diskusi.status_diskusi as discussion_status',
                'author.nama_user as author_name',
                'author.email_user as author_email'
            )
            ->first();

        if (! $report) {
            return response()->json(['status' => 'error', 'message' => 'Laporan diskusi tidak ditemukan.'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $report,
        ]);
    }

    /**
     * Admin: Takedown a reported discussion.
     * Supports passing either $discussionId or $reportId.
     */
    public function takedown(Request $request, $id)
    {
        // /admin/discussion-reports/{id}/takedown -> $id = id laporan
        // /admin/discussions/{id}/takedown       -> $id = id diskusi
        // Dulu $id selalu dicoba sebagai id laporan dulu, jadi kalau id diskusi kebetulan
        // sama dengan id laporan lain, diskusi yang salah ikut ter-takedown.
        if ($this->isReportRoute($request)) {
            $report = DB::table('laporan_diskusi')->where('id_laporan_diskusi', $id)->first();
            if (! $report) {
                return response()->json(['status' => 'error', 'message' => 'Laporan diskusi tidak ditemukan.'], 404);
            }
            $discussionId = $report->id_diskusi;
        } else {
            $discussionId = $id;
        }

        $discussion = DB::table('diskusi')->where('id_diskusi', $discussionId)->first();
        if (! $discussion) {
            return response()->json(['status' => 'error', 'message' => 'Diskusi tidak ditemukan.'], 404);
        }

        // Ubah status jadi 'deleted' agar seragam dengan fungsi destroy di DiscussionController
        DB::table('diskusi')
            ->where('id_diskusi', $discussionId)
            ->update([
                'status_diskusi' => 'deleted',
                'updated_at' => now(),
            ]);

        // Tandai semua laporan terkait diskusi ini sebagai 'resolved'
        DB::table('laporan_diskusi')
            ->where('id_diskusi', $discussionId)
            ->update([
                'status' => 'resolved',
                'updated_at' => now(),
            ]);

        $adminId = $request->user() ? ($request->user()->id_user ?? $request->user()->id) : null;
        try {
            DB::table('log_aktivitas')->insert([
                'id_user' => $adminId,
                'tipe_aksi' => 'Takedown Diskusi',
                'deskripsi' => 'Admin menghapus (takedown) topik diskusi ID: '.$discussionId.' ("'.$discussion->judul_diskusi.'") karena laporan pengguna.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Diskusi berhasil di-takedown dan semua laporan terkait ditandai selesai (resolved).',
        ]);
    }

    /**
     * Admin: Dismiss a discussion report (mark as dismissed/not a violation).
     */
    public function dismiss(Request $request, $id)
    {
        // Sama seperti takedown: id laporan hanya untuk rute /admin/discussion-reports/{id}
        $report = $this->isReportRoute($request)
            ? DB::table('laporan_diskusi')->where('id_laporan_diskusi', $id)->first()
            : null;

        if (! $report && $this->isReportRoute($request)) {
            return response()->json(['status' => 'error', 'message' => 'Laporan diskusi tidak ditemukan.'], 404);
        }

        if (! $report) {
            // Rute /admin/discussions/{id}/dismiss: $id adalah id diskusi
            $reportsCount = DB::table('laporan_diskusi')->where('id_diskusi', $id)->count();
            if ($reportsCount > 0) {
                DB::table('laporan_diskusi')->where('id_diskusi', $id)->update([
                    'status' => 'dismissed',
                    'updated_at' => now(),
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Semua laporan terkait diskusi ini berhasil diabaikan/ditandai tidak melanggar aturan.',
                ]);
            }

            return response()->json(['status' => 'error', 'message' => 'Laporan diskusi tidak ditemukan.'], 404);
        }

        DB::table('laporan_diskusi')->where('id_laporan_diskusi', $id)->update([
            'status' => 'dismissed',
            'updated_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Laporan diskusi berhasil diabaikan/ditandai tidak melanggar aturan.',
        ]);
    }

    /**
     * True jika request datang dari rute berbasis id laporan (/admin/discussion-reports/{id}/...).
     */
    private function isReportRoute(Request $request): bool
    {
        return $request->is('api/admin/discussion-reports/*');
    }

    /**
     * Admin: Delete a report record completely.
     */
    public function destroy(Request $request, $id)
    {
        $report = DB::table('laporan_diskusi')->where('id_laporan_diskusi', $id)->first();
        if (! $report) {
            return response()->json(['status' => 'error', 'message' => 'Laporan tidak ditemukan.'], 404);
        }

        DB::table('laporan_diskusi')->where('id_laporan_diskusi', $id)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Data laporan diskusi berhasil dihapus.',
        ]);
    }
}
