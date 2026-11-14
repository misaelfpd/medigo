<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Admin: laporan diskusi (topik) dan laporan komentar diskusi (tanggapan).
 * Semua data diambil dari backend; aksi takedown/abaikan memakai ID laporan.
 */
class AdminDiscussionReportController extends Controller
{
    private const STATUS_FILTERS = ['pending', 'resolved', 'dismissed'];

    private function backendUrl(): string
    {
        $raw = config('services.medigo.url', env('MEDIGO_BE', 'http://127.0.0.1:8005'));

        return rtrim(str_replace('localhost', '127.0.0.1', $raw), '/');
    }

    private function statusFilter(Request $request): ?string
    {
        $status = $request->query('status');

        return in_array($status, self::STATUS_FILTERS, true) ? $status : null;
    }

    /**
     * Ambil daftar laporan dari backend. Mengembalikan [items, gagal?].
     */
    private function fetchReports(string $path, Request $request): array
    {
        $params = array_filter([
            'status' => $this->statusFilter($request),
            'search' => trim((string) $request->query('search', '')) ?: null,
        ]);

        try {
            $response = Http::timeout(6)->withToken(session('admin_token'))->acceptJson()
                ->get($this->backendUrl() . $path, $params);

            if ($response->successful()) {
                return [collect($response->json('data', []))->map(fn ($r) => (array) $r)->all(), false];
            }
            Log::warning("Admin report list {$path} failed: HTTP " . $response->status());
        } catch (\Exception $e) {
            Log::error("Admin report list {$path} failed: " . $e->getMessage());
        }

        return [[], true];
    }

    /**
     * Kirim aksi ke backend lalu kembali dengan pesan dari backend.
     */
    private function sendAction(string $path, string $redirectRoute, string $fallbackError)
    {
        try {
            $response = Http::timeout(6)->withToken(session('admin_token'))->acceptJson()
                ->post($this->backendUrl() . $path);

            if ($response->successful()) {
                return back()->with('success', $response->json('message') ?: 'Berhasil diperbarui.');
            }

            return back()->with('error', $response->json('message') ?: $fallbackError);
        } catch (\Exception $e) {
            Log::error("Admin report action {$path} failed: " . $e->getMessage());
        }

        return redirect()->route($redirectRoute)->with('error', $fallbackError);
    }

    private function ensureLogin()
    {
        return session('admin_token')
            ? null
            : redirect()->route('admin.login')->with('error', 'Silakan login terlebih dahulu.');
    }

    // ===================== Laporan Diskusi =====================

    public function index(Request $request)
    {
        if ($redirect = $this->ensureLogin()) {
            return $redirect;
        }

        [$reports, $loadFailed] = $this->fetchReports('/api/admin/discussion-reports', $request);
        $statusFilter = $this->statusFilter($request);

        return view('admin.discussion_reports', compact('reports', 'loadFailed', 'statusFilter'));
    }

    public function takedownReport($id)
    {
        if ($redirect = $this->ensureLogin()) {
            return $redirect;
        }

        return $this->sendAction("/api/admin/discussion-reports/{$id}/takedown", 'admin.discussion-reports', 'Gagal men-takedown diskusi.');
    }

    public function dismissReport($id)
    {
        if ($redirect = $this->ensureLogin()) {
            return $redirect;
        }

        return $this->sendAction("/api/admin/discussion-reports/{$id}/dismiss", 'admin.discussion-reports', 'Gagal mengabaikan laporan.');
    }

    /**
     * Rute lama: takedown berdasarkan ID diskusi (dipakai halaman moderasi forum).
     */
    public function takedown($id)
    {
        if ($redirect = $this->ensureLogin()) {
            return $redirect;
        }

        return $this->sendAction("/api/admin/discussions/{$id}/takedown", 'admin.discussion-reports', 'Gagal men-takedown diskusi.');
    }

    // ===================== Laporan Komentar Diskusi =====================

    public function indexComments(Request $request)
    {
        if ($redirect = $this->ensureLogin()) {
            return $redirect;
        }

        [$reports, $loadFailed] = $this->fetchReports('/api/admin/discussion-comment-reports', $request);
        $statusFilter = $this->statusFilter($request);

        return view('admin.discussion_comment_reports', compact('reports', 'loadFailed', 'statusFilter'));
    }

    public function takedownComment($id)
    {
        if ($redirect = $this->ensureLogin()) {
            return $redirect;
        }

        return $this->sendAction("/api/admin/discussion-comment-reports/{$id}/takedown", 'admin.discussion-comment-reports', 'Gagal menghapus komentar diskusi.');
    }

    public function dismissComment($id)
    {
        if ($redirect = $this->ensureLogin()) {
            return $redirect;
        }

        return $this->sendAction("/api/admin/discussion-comment-reports/{$id}/dismiss", 'admin.discussion-comment-reports', 'Gagal mengabaikan laporan.');
    }
}
