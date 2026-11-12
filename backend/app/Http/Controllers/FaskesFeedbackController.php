<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

class FaskesFeedbackController extends Controller
{
    /**
     * Submit feedback for a health facility (faskes).
     * Open to authenticated users and guests.
     */
    public function store(Request $request, $faskes_id)
    {
        $faskes = DB::table('faskes')->where('id_faskes', $faskes_id)->first();
        if (! $faskes) {
            return response()->json(['status' => 'error', 'message' => 'Fasilitas kesehatan tidak ditemukan.'], 404);
        }

        // Support both English and Indonesian input names
        $kategori = $request->input('category', $request->input('kategori'));
        $catatan = $request->input('notes', $request->input('catatan'));
        $saranKoreksi = $request->input('suggested_correction', $request->input('saran_koreksi'));
        $userName = $request->input('user_name', $request->input('nama_pengguna'));
        $userEmail = $request->input('user_email', $request->input('email_pengguna'));

        $request->merge([
            'category' => $kategori,
            'notes' => $catatan,
            'suggested_correction' => $saranKoreksi,
            'user_name' => $userName,
            'user_email' => $userEmail,
        ]);

        $request->validate([
            'category' => 'required|string|max:100',
            'notes' => 'required|string|max:2000',
            'suggested_correction' => 'nullable|string|max:1000',
            'user_name' => 'nullable|string|max:150',
            'user_email' => 'nullable|email|max:150',
        ], [
            'category.required' => 'Kategori masukan/laporan wajib dipilih.',
            'notes.required' => 'Catatan laporan mengenai faskes wajib diisi.',
        ]);

        $userId = null;

        // Check if user is authenticated via Sanctum token
        if ($request->user()) {
            $userId = $request->user()->id_user ?? $request->user()->id;
            $userName = $userName ?: ($request->user()->nama_user ?? $request->user()->name);
            $userEmail = $userEmail ?: ($request->user()->email_user ?? $request->user()->email);
        } elseif ($bearer = $request->bearerToken()) {
            $tokenObj = PersonalAccessToken::findToken($bearer);
            if ($tokenObj && $tokenObj->tokenable) {
                $user = $tokenObj->tokenable;
                $userId = $user->id_user ?? $user->id;
                $userName = $userName ?: ($user->nama_user ?? $user->name);
                $userEmail = $userEmail ?: ($user->email_user ?? $user->email);
            }
        }

        $feedbackId = DB::table('feedback_faskes')->insertGetId([
            'id_faskes' => $faskes_id,
            'id_user' => $userId,
            'nama_pengguna' => $userName ?: 'Pengguna Anonim',
            'email_pengguna' => $userEmail,
            'kategori' => $kategori,
            'catatan' => $catatan,
            'saran_koreksi' => $saranKoreksi,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $feedback = DB::table('feedback_faskes')->where('id_feedback_faskes', $feedbackId)->first();

        return response()->json([
            'status' => 'success',
            'message' => 'Terima kasih! Masukan Anda mengenai data fasilitas kesehatan ini berhasil dikirim dan akan segera ditinjau oleh tim admin.',
            'data' => $feedback,
        ], 201);
    }

    /**
     * Admin: List all faskes feedbacks with facility details
     */
    public function indexAdmin(Request $request)
    {
        $query = DB::table('feedback_faskes')
            ->join('faskes', 'feedback_faskes.id_faskes', '=', 'faskes.id_faskes')
            ->leftJoin('user', 'feedback_faskes.id_user', '=', 'user.id_user')
            ->select(
                'feedback_faskes.*',
                'faskes.nama_faskes',
                'faskes.jenis_faskes as faskes_tipe',
                'faskes.kota_kabupaten',
                'faskes.alamat_faskes as faskes_alamat',
                'faskes.link_maps as faskes_link_maps',
                'faskes._support_bpjs as faskes_bpjs',
                'user.nama_user as registered_user_name',
                'user.email_user as registered_user_email'
            );

        if ($request->filled('status')) {
            $query->where('feedback_faskes.status', $request->status);
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('faskes.nama_faskes', 'like', "%{$s}%")
                    ->orWhere('feedback_faskes.catatan', 'like', "%{$s}%")
                    ->orWhere('feedback_faskes.nama_pengguna', 'like', "%{$s}%")
                    ->orWhere('feedback_faskes.kategori', 'like', "%{$s}%");
            });
        }

        $feedbacks = $query->orderBy('feedback_faskes.created_at', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'total' => $feedbacks->count(),
            'data' => $feedbacks,
        ]);
    }

    /**
     * Admin: Show detail of a single feedback
     */
    public function show($id)
    {
        $feedback = DB::table('feedback_faskes')
            ->join('faskes', 'feedback_faskes.id_faskes', '=', 'faskes.id_faskes')
            ->leftJoin('user', 'feedback_faskes.id_user', '=', 'user.id_user')
            ->where('feedback_faskes.id_feedback_faskes', $id)
            ->select(
                'feedback_faskes.*',
                'faskes.nama_faskes',
                'faskes.jenis_faskes as faskes_tipe',
                'faskes.kota_kabupaten',
                'faskes.alamat_faskes as faskes_alamat',
                'user.nama_user as registered_user_name',
                'user.email_user as registered_user_email'
            )
            ->first();

        if (! $feedback) {
            return response()->json(['status' => 'error', 'message' => 'Masukan data faskes tidak ditemukan.'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $feedback,
        ]);
    }

    /**
     * Admin: Update status of a feedback (resolved, dismissed, pending)
     */
    public function updateStatus(Request $request, $id)
    {
        $feedback = DB::table('feedback_faskes')->where('id_feedback_faskes', $id)->first();
        if (! $feedback) {
            return response()->json(['status' => 'error', 'message' => 'Feedback tidak ditemukan.'], 404);
        }

        $request->validate([
            'status' => 'required|string',
            'catatan_admin' => 'nullable|string|max:1000',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $statusInput = strtolower(trim($request->input('status')));
        $mappedStatus = in_array($statusInput, ['resolved', 'applied', 'reviewed', 'selesai', 'diterima'])
            ? 'resolved'
            : (in_array($statusInput, ['dismissed', 'rejected', 'tolak', 'abaikan', 'ditolak']) ? 'dismissed' : 'pending');

        $adminNotes = $request->input('catatan_admin', $request->input('admin_notes'));

        $updateData = [
            'status' => $mappedStatus,
            'updated_at' => now(),
        ];

        if ($adminNotes !== null) {
            $updateData['catatan_admin'] = $adminNotes;
        }

        DB::table('feedback_faskes')->where('id_feedback_faskes', $id)->update($updateData);

        $updatedFeedback = DB::table('feedback_faskes')->where('id_feedback_faskes', $id)->first();

        return response()->json([
            'status' => 'success',
            'message' => 'Status masukan data faskes berhasil diperbarui.',
            'data' => $updatedFeedback,
        ]);
    }

    /**
     * Admin: Delete a feedback
     */
    public function destroy(Request $request, $id)
    {
        $feedback = DB::table('feedback_faskes')->where('id_feedback_faskes', $id)->first();
        if (! $feedback) {
            return response()->json(['status' => 'error', 'message' => 'Feedback tidak ditemukan.'], 404);
        }

        DB::table('feedback_faskes')->where('id_feedback_faskes', $id)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Masukan data faskes berhasil dihapus.',
        ]);
    }
}
