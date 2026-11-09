<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdminReportController extends Controller
{
    private function getBackendUrl()
    {
        $rawBackendUrl = config('services.medigo.url', env('MEDIGO_BE', 'http://127.0.0.1:8005'));
        return rtrim(str_replace('localhost', '127.0.0.1', $rawBackendUrl), '/');
    }

    /**
     * List all faskes data feedbacks
     */
    public function indexFeedbacks(Request $request)
    {
        $token = session('admin_token');
        if (!$token) {
            return redirect()->route('admin.login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $feedbacks = [];
        try {
            $backendUrl = $this->getBackendUrl();
            $response = Http::timeout(6)->withToken($token)->get("{$backendUrl}/api/admin/faskes-feedbacks");
            if ($response->successful()) {
                $rawFeedbacks = $response->json()['data'] ?? [];
                $feedbacks = collect($rawFeedbacks)->map(function ($fb) {
                    $fb = (array) $fb;
                    $id = $fb['id_feedback_faskes'] ?? ($fb['id'] ?? null);
                    $category = $fb['kategori'] ?? ($fb['category'] ?? 'lainnya');
                    $notes = $fb['catatan'] ?? ($fb['notes'] ?? '');
                    $suggestion = $fb['saran_koreksi'] ?? ($fb['suggested_correction'] ?? null);
                    $userName = $fb['nama_pengguna'] ?? ($fb['user_name'] ?? ($fb['registered_user_name'] ?? 'Pengguna Anonim'));
                    $userEmail = $fb['email_pengguna'] ?? ($fb['user_email'] ?? ($fb['registered_user_email'] ?? null));
                    $adminNotes = $fb['catatan_admin'] ?? ($fb['admin_notes'] ?? null);

                    return (object) array_merge($fb, [
                        'id' => $id,
                        'id_feedback_faskes' => $id,
                        'category' => $category,
                        'kategori' => $category,
                        'notes' => $notes,
                        'catatan' => $notes,
                        'suggested_correction' => $suggestion,
                        'saran_koreksi' => $suggestion,
                        'user_name' => $userName,
                        'nama_pengguna' => $userName,
                        'user_email' => $userEmail,
                        'email_pengguna' => $userEmail,
                        'admin_notes' => $adminNotes,
                        'catatan_admin' => $adminNotes,
                    ]);
                })->all();
            }
        } catch (\Exception $e) {
            Log::error('Failed fetching faskes feedbacks: ' . $e->getMessage());
        }

        return view('admin.faskes_feedbacks', compact('feedbacks'));
    }

    /**
     * Update feedback status (reviewed, applied, rejected)
     */
    public function updateFeedbackStatus(Request $request, $id)
    {
        $token = session('admin_token');
        if (!$token) {
            return redirect()->route('admin.login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $request->validate([
            'status' => 'required|in:pending,reviewed,applied,rejected,resolved,dismissed',
            'admin_notes' => 'nullable|string|max:1000',
            'catatan_admin' => 'nullable|string|max:1000',
        ]);

        // Map status to backend enum values: pending, resolved, dismissed
        $statusInput = $request->input('status');
        $mappedStatus = in_array($statusInput, ['resolved', 'applied', 'reviewed']) ? 'resolved' : ($statusInput === 'rejected' ? 'dismissed' : 'pending');
        $adminNotes = $request->input('catatan_admin', $request->input('admin_notes'));

        try {
            $backendUrl = $this->getBackendUrl();
            $response = Http::timeout(6)->withToken($token)->put("{$backendUrl}/api/admin/faskes-feedbacks/{$id}/status", [
                'status' => $mappedStatus,
                'catatan_admin' => $adminNotes,
                'admin_notes' => $adminNotes,
            ]);

            if ($response->successful()) {
                return redirect()->route('admin.faskes-feedbacks')->with('success', 'Status masukan data berhasil diperbarui.');
            }
        } catch (\Exception $e) {
            Log::error('Failed updating feedback status: ' . $e->getMessage());
        }

        return redirect()->route('admin.faskes-feedbacks')->with('error', 'Gagal memperbarui status masukan.');
    }

    /**
     * List all reported faskes comments
     */
    public function indexCommentReports(Request $request)
    {
        $token = session('admin_token');
        if (!$token) {
            return redirect()->route('admin.login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $reports = [];
        try {
            $backendUrl = $this->getBackendUrl();
            $response = Http::timeout(6)->withToken($token)->get("{$backendUrl}/api/admin/faskes-comment-reports");
            if ($response->successful()) {
                $rawReports = $response->json()['data'] ?? [];
                $reports = collect($rawReports)->map(function ($rep) {
                    $rep = (array) $rep;
                    $id = $rep['id_laporan_komentar'] ?? ($rep['id'] ?? null);
                    $reason = $rep['alasan_laporan'] ?? ($rep['reason'] ?? '');
                    $notes = $rep['catatan_tambahan'] ?? ($rep['notes'] ?? null);

                    return (object) array_merge($rep, [
                        'id' => $id,
                        'id_laporan_komentar' => $id,
                        'reason' => $reason,
                        'alasan_laporan' => $reason,
                        'notes' => $notes,
                        'catatan_tambahan' => $notes,
                    ]);
                })->all();
            }
        } catch (\Exception $e) {
            Log::error('Failed fetching comment reports: ' . $e->getMessage());
        }

        return view('admin.comment_reports', compact('reports'));
    }

    /**
     * Takedown / delete a reported comment
     */
    public function takedownComment(Request $request, $id)
    {
        $token = session('admin_token');
        if (!$token) {
            return redirect()->route('admin.login')->with('error', 'Silakan login terlebih dahulu.');
        }

        try {
            $backendUrl = $this->getBackendUrl();
            $response = Http::timeout(6)->withToken($token)->post("{$backendUrl}/api/admin/faskes-comment-reports/{$id}/takedown");

            if ($response->successful()) {
                return redirect()->route('admin.comment-reports')->with('success', 'Komentar yang melanggar berhasil dihapus (takedown).');
            }
        } catch (\Exception $e) {
            Log::error('Failed takedown comment report: ' . $e->getMessage());
        }

        return redirect()->route('admin.comment-reports')->with('error', 'Gagal men-takedown komentar.');
    }

    /**
     * Dismiss a comment report
     */
    public function dismissCommentReport(Request $request, $id)
    {
        $token = session('admin_token');
        if (!$token) {
            return redirect()->route('admin.login')->with('error', 'Silakan login terlebih dahulu.');
        }

        try {
            $backendUrl = $this->getBackendUrl();
            $response = Http::timeout(6)->withToken($token)->post("{$backendUrl}/api/admin/faskes-comment-reports/{$id}/dismiss");

            if ($response->successful()) {
                return redirect()->route('admin.comment-reports')->with('success', 'Laporan berhasil ditandai selesai/diabaikan.');
            }
        } catch (\Exception $e) {
            Log::error('Failed dismissing comment report: ' . $e->getMessage());
        }

        return redirect()->route('admin.comment-reports')->with('error', 'Gagal memperbarui status laporan.');
    }
}
