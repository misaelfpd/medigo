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
     * Daftar masukan/koreksi data faskes dari pengguna (bisa difilter status & pencarian)
     */
    public function indexFeedbacks(Request $request)
    {
        $token = session('admin_token');
        if (!$token) {
            return redirect()->route('admin.login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $statusFilter = in_array($request->query('status'), ['pending', 'resolved', 'dismissed'], true) ? $request->query('status') : null;
        $params = array_filter([
            'status' => $statusFilter,
            'search' => trim((string) $request->query('search', '')) ?: null,
        ]);

        $feedbacks = [];
        $loadFailed = false;
        try {
            $response = Http::timeout(6)->withToken($token)->acceptJson()
                ->get($this->getBackendUrl() . '/api/admin/faskes-feedbacks', $params);
            if ($response->successful()) {
                $feedbacks = collect($response->json('data', []))->map(fn ($fb) => (array) $fb)->all();
            } else {
                $loadFailed = true;
            }
        } catch (\Exception $e) {
            $loadFailed = true;
            Log::error('Failed fetching faskes feedbacks: ' . $e->getMessage());
        }

        return view('admin.faskes_feedbacks', compact('feedbacks', 'loadFailed', 'statusFilter'));
    }

    /**
     * Ubah status masukan: pending (menunggu), resolved (data sudah disesuaikan), dismissed (ditolak)
     */
    public function updateFeedbackStatus(Request $request, $id)
    {
        $token = session('admin_token');
        if (!$token) {
            return redirect()->route('admin.login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,resolved,dismissed',
            'admin_notes' => 'nullable|string|max:1000',
        ], [
            'status.in' => 'Status masukan tidak valid.',
        ]);

        try {
            $response = Http::timeout(6)->withToken($token)->acceptJson()
                ->put($this->getBackendUrl() . "/api/admin/faskes-feedbacks/{$id}/status", [
                    'status' => $validated['status'],
                    'catatan_admin' => $validated['admin_notes'] ?? '',
                ]);

            if ($response->successful()) {
                return back()->with('success', $response->json('message') ?: 'Status masukan data berhasil diperbarui.');
            }

            return back()->with('error', $response->json('message') ?: 'Gagal memperbarui status masukan.');
        } catch (\Exception $e) {
            Log::error('Failed updating feedback status: ' . $e->getMessage());
        }

        return back()->with('error', 'Gagal memperbarui status masukan.');
    }

    /**
     * Daftar laporan ulasan faskes (bisa difilter status & pencarian)
     */
    public function indexCommentReports(Request $request)
    {
        $token = session('admin_token');
        if (!$token) {
            return redirect()->route('admin.login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $statusFilter = in_array($request->query('status'), ['pending', 'resolved', 'dismissed'], true) ? $request->query('status') : null;
        $params = array_filter([
            'status' => $statusFilter,
            'search' => trim((string) $request->query('search', '')) ?: null,
        ]);

        $reports = [];
        $loadFailed = false;
        try {
            $response = Http::timeout(6)->withToken($token)->acceptJson()
                ->get($this->getBackendUrl() . '/api/admin/faskes-comment-reports', $params);
            if ($response->successful()) {
                $reports = collect($response->json('data', []))->map(fn ($r) => (array) $r)->all();
            } else {
                $loadFailed = true;
            }
        } catch (\Exception $e) {
            $loadFailed = true;
            Log::error('Failed fetching comment reports: ' . $e->getMessage());
        }

        return view('admin.comment_reports', compact('reports', 'loadFailed', 'statusFilter'));
    }

    private function commentReportAction(string $path, string $fallbackError)
    {
        if (!session('admin_token')) {
            return redirect()->route('admin.login')->with('error', 'Silakan login terlebih dahulu.');
        }

        try {
            $response = Http::timeout(6)->withToken(session('admin_token'))->acceptJson()
                ->post($this->getBackendUrl() . $path);

            if ($response->successful()) {
                return back()->with('success', $response->json('message') ?: 'Laporan berhasil diperbarui.');
            }

            return back()->with('error', $response->json('message') ?: $fallbackError);
        } catch (\Exception $e) {
            Log::error("Comment report action {$path} failed: " . $e->getMessage());
        }

        return redirect()->route('admin.comment-reports')->with('error', $fallbackError);
    }

    /**
     * Takedown (hapus) ulasan faskes yang dilaporkan
     */
    public function takedownComment(Request $request, $id)
    {
        return $this->commentReportAction("/api/admin/faskes-comment-reports/{$id}/takedown", 'Gagal men-takedown ulasan.');
    }

    /**
     * Abaikan laporan ulasan faskes
     */
    public function dismissCommentReport(Request $request, $id)
    {
        return $this->commentReportAction("/api/admin/faskes-comment-reports/{$id}/dismiss", 'Gagal memperbarui status laporan.');
    }
}
