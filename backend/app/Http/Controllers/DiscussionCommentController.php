<?php

namespace App\Http\Controllers;

use App\Models\TanggapanDiskusi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\PersonalAccessToken;

class DiscussionCommentController extends Controller
{
    public function store(Request $request, $discussion_id)
    {
        $request->validate([
            'body' => 'required|string',
            'image' => 'nullable|image|max:5120',
            'parent_id' => 'nullable|exists:tanggapan_diskusi,id_tanggapan',
        ]);

        $discussionExists = DB::table('diskusi')
            ->where('id_diskusi', $discussion_id)
            ->where('status_diskusi', 'active')
            ->exists();

        if (! $discussionExists) {
            return response()->json(['message' => 'Diskusi tidak ditemukan atau telah ditutup'], 404);
        }

        $imageUrl = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('comments', 'public');
            $imageUrl = url('storage/'.$path);
        }

        $currentUserId = $request->user()->id_user ?? $request->user()->id;

        $commentData = [
            'id_diskusi' => $discussion_id,
            'id_user' => $currentUserId,
            'isi_tanggapan' => $request->input('body'),
            'gambar_tanggapan' => $imageUrl,
            'id_parent' => $request->input('parent_id'),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $commentId = DB::table('tanggapan_diskusi')->insertGetId($commentData);

        return response()->json([
            'message' => 'Komentar berhasil ditambahkan',
            'comment_id' => $commentId,
        ], 201);
    }

    public function report(Request $request, $id)
    {
        $reason = $request->input('reason', $request->input('alasan_laporan'));
        $request->merge(['reason' => $reason]);

        $request->validate([
            'reason' => 'required|string|max:1000',
        ], [
            'reason.required' => 'Alasan laporan komentar wajib diisi.',
        ]);

        $comment = DB::table('tanggapan_diskusi')->where('id_tanggapan', $id)->first();

        if (! $comment) {
            return response()->json(['status' => 'error', 'message' => 'Komentar tidak ditemukan.'], 404);
        }

        $currentUser = $request->user();
        if (! $currentUser && ($bearer = $request->bearerToken())) {
            $tokenObj = PersonalAccessToken::findToken($bearer);
            if ($tokenObj && $tokenObj->tokenable) {
                $currentUser = $tokenObj->tokenable;
            }
        }

        if (! $currentUser) {
            return response()->json(['status' => 'error', 'message' => 'Silakan masuk terlebih dahulu untuk melaporkan komentar.'], 401);
        }

        $currentUserId = $currentUser->id_user ?? $currentUser->id;

        if ((int) $comment->id_user === (int) $currentUserId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak dapat melaporkan komentar Anda sendiri.',
            ], 422);
        }

        $alreadyReported = DB::table('laporan_tanggapan')
            ->where('id_tanggapan', $id)
            ->where('id_user', $currentUserId)
            ->exists();

        if ($alreadyReported) {
            return response()->json([
                'status' => 'success',
                'message' => 'Anda sudah pernah melaporkan komentar ini sebelumnya. Laporan Anda sedang dalam antrean peninjauan admin.',
            ]);
        }

        $reportId = DB::table('laporan_tanggapan')->insertGetId([
            'id_tanggapan' => $id,
            'id_user' => $currentUserId,
            'alasan_laporan' => $reason,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $report = DB::table('laporan_tanggapan')->where('id_laporan_tanggapan', $reportId)->first();

        return response()->json([
            'status' => 'success',
            'message' => 'Laporan komentar diskusi berhasil dikirim dan akan segera ditinjau oleh admin.',
            'data' => $report,
        ], 201);
    }

    public function destroy(Request $request, $id)
    {
        $comment = TanggapanDiskusi::find($id);
        if (! $comment) {
            return response()->json(['status' => 'error', 'message' => 'Komentar tidak ditemukan'], 404);
        }

        $currentUser = $request->user();
        $isAdmin = method_exists($currentUser, 'isAdmin') ? $currentUser->isAdmin() : (($currentUser->role_user ?? $currentUser->role ?? '') === 'admin');

        $currentUserId = $currentUser->id_user ?? $currentUser->id;

        if ((int) $comment->id_user !== (int) $currentUserId && ! $isAdmin) {
            return response()->json(['status' => 'error', 'message' => 'Anda tidak memiliki izin untuk menghapus komentar ini'], 403);
        }

        if ($comment->gambar_tanggapan) {
            $path = str_replace(url('storage/'), '', $comment->gambar_tanggapan);
            Storage::disk('public')->delete($path);
        }

        $comment->delete();

        return response()->json(['status' => 'success', 'message' => 'Balasan berhasil dihapus']);
    }

    /**
     * Admin: List all reported discussion comments
     */
    public function indexAdminReports(Request $request)
    {
        $query = DB::table('laporan_tanggapan')
            ->join('user as reporter', 'laporan_tanggapan.id_user', '=', 'reporter.id_user')
            ->leftJoin('tanggapan_diskusi', 'laporan_tanggapan.id_tanggapan', '=', 'tanggapan_diskusi.id_tanggapan')
            ->leftJoin('diskusi', 'tanggapan_diskusi.id_diskusi', '=', 'diskusi.id_diskusi')
            ->leftJoin('user as author', 'tanggapan_diskusi.id_user', '=', 'author.id_user')
            ->select(
                'laporan_tanggapan.*',
                'reporter.nama_user as reporter_name',
                'reporter.email_user as reporter_email',
                'diskusi.id_diskusi',
                'diskusi.judul_diskusi as discussion_title',
                'tanggapan_diskusi.isi_tanggapan as comment_body',
                'tanggapan_diskusi.gambar_tanggapan as comment_image',
                'tanggapan_diskusi.created_at as comment_created_at',
                'author.nama_user as author_name',
                'author.email_user as author_email'
            );

        if ($request->filled('status')) {
            $query->where('laporan_tanggapan.status', $request->status);
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('tanggapan_diskusi.isi_tanggapan', 'like', "%{$s}%")
                    ->orWhere('laporan_tanggapan.alasan_laporan', 'like', "%{$s}%")
                    ->orWhere('reporter.nama_user', 'like', "%{$s}%")
                    ->orWhere('diskusi.judul_diskusi', 'like', "%{$s}%");
            });
        }

        $reports = $query->orderBy('laporan_tanggapan.created_at', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil mengambil daftar laporan komentar diskusi',
            'total' => $reports->count(),
            'data' => $reports,
        ]);
    }

    /**
     * Admin: Show detail of a single discussion comment report
     */
    public function showReportAdmin($id)
    {
        $report = DB::table('laporan_tanggapan')
            ->join('user as reporter', 'laporan_tanggapan.id_user', '=', 'reporter.id_user')
            ->leftJoin('tanggapan_diskusi', 'laporan_tanggapan.id_tanggapan', '=', 'tanggapan_diskusi.id_tanggapan')
            ->leftJoin('diskusi', 'tanggapan_diskusi.id_diskusi', '=', 'diskusi.id_diskusi')
            ->leftJoin('user as author', 'tanggapan_diskusi.id_user', '=', 'author.id_user')
            ->where('laporan_tanggapan.id_laporan_tanggapan', $id)
            ->select(
                'laporan_tanggapan.*',
                'reporter.nama_user as reporter_name',
                'reporter.email_user as reporter_email',
                'diskusi.id_diskusi',
                'diskusi.judul_diskusi as discussion_title',
                'tanggapan_diskusi.isi_tanggapan as comment_body',
                'tanggapan_diskusi.created_at as comment_created_at',
                'author.nama_user as author_name',
                'author.email_user as author_email'
            )
            ->first();

        if (! $report) {
            return response()->json(['status' => 'error', 'message' => 'Laporan tidak ditemukan.'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $report,
        ]);
    }

    /**
     * Admin: Takedown/Delete a reported discussion comment
     */
    public function takedownReportAdmin(Request $request, $id)
    {
        $report = DB::table('laporan_tanggapan')->where('id_laporan_tanggapan', $id)->first();
        $tanggapanId = $report ? $report->id_tanggapan : $id;

        $comment = DB::table('tanggapan_diskusi')->where('id_tanggapan', $tanggapanId)->first();
        if ($comment) {
            if ($comment->gambar_tanggapan) {
                $path = str_replace(url('storage/'), '', $comment->gambar_tanggapan);
                Storage::disk('public')->delete($path);
            }
            DB::table('tanggapan_diskusi')->where('id_tanggapan', $tanggapanId)->delete();
        }

        // Mark all reports for this discussion comment as resolved
        DB::table('laporan_tanggapan')
            ->where('id_tanggapan', $tanggapanId)
            ->update([
                'status' => 'resolved',
                'updated_at' => now(),
            ]);

        $adminId = $request->user() ? ($request->user()->id_user ?? $request->user()->id) : null;
        try {
            DB::table('log_aktivitas')->insert([
                'id_user' => $adminId,
                'tipe_aksi' => 'Takedown Komentar Diskusi',
                'deskripsi' => 'Admin menghapus tanggapan/komentar diskusi ID: '.$tanggapanId.' karena laporan pengguna.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Komentar diskusi berhasil di-takedown dan semua laporan terkait ditandai selesai.',
        ]);
    }

    /**
     * Admin: Dismiss a discussion comment report
     */
    public function dismissReportAdmin(Request $request, $id)
    {
        $report = DB::table('laporan_tanggapan')->where('id_laporan_tanggapan', $id)->first();
        if (! $report) {
            $count = DB::table('laporan_tanggapan')->where('id_tanggapan', $id)->count();
            if ($count > 0) {
                DB::table('laporan_tanggapan')->where('id_tanggapan', $id)->update([
                    'status' => 'dismissed',
                    'updated_at' => now(),
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Laporan komentar diskusi berhasil diabaikan/ditandai tidak melanggar.',
                ]);
            }

            return response()->json(['status' => 'error', 'message' => 'Laporan tidak ditemukan.'], 404);
        }

        DB::table('laporan_tanggapan')->where('id_laporan_tanggapan', $id)->update([
            'status' => 'dismissed',
            'updated_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Laporan komentar diskusi berhasil diabaikan/ditandai tidak melanggar.',
        ]);
    }
}
