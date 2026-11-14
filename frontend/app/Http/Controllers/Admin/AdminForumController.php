<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdminForumController extends Controller
{
    private function getBackendUrl(): string
    {
        $rawBackendUrl = config('services.medigo.url', env('MEDIGO_BE', 'http://127.0.0.1:8000'));
        return rtrim(str_replace('localhost', '127.0.0.1', $rawBackendUrl), '/');
    }

    /**
     * Tampilkan Halaman Moderasi Forum
     */
    public function index(Request $request)
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login')->with('error', 'Silakan masuk terlebih dahulu untuk mengakses menu admin.');
        }

        $token = session('admin_token');
        $backendUrl = $this->getBackendUrl();

        $tab = $request->input('tab', 'reported'); // reported, recent, trending
        $sort = $request->input('sort', 'latest'); // latest, oldest, reports_desc, comments_desc
        $search = trim($request->input('search', ''));
        $page = max(1, (int) $request->input('page', 1));
        $perPage = 10;

        $stats = [
            'active_threads' => 0,
            'pending_reports' => 0,
            'auto_actions' => 0,
        ];

        $pagination = [
            'current_page' => $page,
            'last_page' => 1,
            'total' => 0,
            'from' => 0,
            'to' => 0,
            'per_page' => $perPage,
        ];

        $threads = [];

        try {
            $http = Http::timeout(6);
            if ($token) {
                $http = $http->withToken($token);
            }

            $response = $http->get("{$backendUrl}/api/admin/forum/moderation", [
                'tab' => $tab,
                'sort' => $sort,
                'search' => $search,
                'page' => $page,
                'per_page' => $perPage,
            ]);

            if ($response->successful()) {
                $body = $response->json();
                $stats = $body['stats'] ?? $stats;
                $pagination = $body['pagination'] ?? $pagination;
                $threads = $body['data'] ?? [];
            }
        } catch (\Exception $e) {
            Log::warning('Failed fetching forum moderation data: ' . $e->getMessage());
        }

        return view('admin.forum', compact('stats', 'pagination', 'threads', 'tab', 'sort', 'search'));
    }

    /**
     * Hapus / Takedown Postingan Diskusi
     */
    public function takedownDiscussion(Request $request, $id)
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login')->with('error', 'Silakan masuk terlebih dahulu.');
        }

        if ($request->input('type') === 'comment') {
            return $this->takedownComment($request, $id);
        }

        $token = session('admin_token');
        $backendUrl = $this->getBackendUrl();

        try {
            $http = Http::timeout(6);
            if ($token) {
                $http = $http->withToken($token);
            }

            $response = $http->post("{$backendUrl}/api/admin/forum/discussions/{$id}/takedown");
            if ($response->successful()) {
                return back()->with('success', 'Postingan diskusi berhasil dihapus / di-takedown dari forum.');
            }
        } catch (\Exception $e) {
            Log::error('Failed takedown discussion: ' . $e->getMessage());
        }

        return back()->with('error', 'Gagal menghapus postingan diskusi. Silakan coba beberapa saat lagi.');
    }

    /**
     * Hapus / Takedown Komentar Diskusi
     */
    public function takedownComment(Request $request, $id)
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login')->with('error', 'Silakan masuk terlebih dahulu.');
        }

        $token = session('admin_token');
        $backendUrl = $this->getBackendUrl();

        try {
            $http = Http::timeout(6);
            if ($token) {
                $http = $http->withToken($token);
            }

            $response = $http->post("{$backendUrl}/api/admin/forum/comments/{$id}/takedown");
            if ($response->successful()) {
                return back()->with('success', 'Komentar diskusi berhasil dihapus dari forum.');
            }
        } catch (\Exception $e) {
            Log::error('Failed takedown comment: ' . $e->getMessage());
        }

        return back()->with('error', 'Gagal menghapus komentar diskusi. Silakan coba beberapa saat lagi.');
    }

    /**
     * Abaikan Laporan Diskusi (Tandai bukan pelanggaran)
     */
    public function dismissDiscussionReport(Request $request, $id)
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login')->with('error', 'Silakan masuk terlebih dahulu.');
        }

        if ($request->input('type') === 'comment') {
            return $this->dismissCommentReport($request, $id);
        }

        $token = session('admin_token');
        $backendUrl = $this->getBackendUrl();

        try {
            $http = Http::timeout(6);
            if ($token) {
                $http = $http->withToken($token);
            }

            $response = $http->post("{$backendUrl}/api/admin/forum/discussions/{$id}/dismiss");
            if ($response->successful()) {
                return back()->with('success', 'Laporan diskusi berhasil diabaikan dan ditandai bukan pelanggaran.');
            }
        } catch (\Exception $e) {
            Log::error('Failed dismiss discussion report: ' . $e->getMessage());
        }

        return back()->with('error', 'Gagal mengabaikan laporan diskusi.');
    }

    /**
     * Abaikan Laporan Komentar Diskusi
     */
    public function dismissCommentReport(Request $request, $id)
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login')->with('error', 'Silakan masuk terlebih dahulu.');
        }

        $token = session('admin_token');
        $backendUrl = $this->getBackendUrl();

        try {
            $http = Http::timeout(6);
            if ($token) {
                $http = $http->withToken($token);
            }

            $response = $http->post("{$backendUrl}/api/admin/forum/comments/{$id}/dismiss");
            if ($response->successful()) {
                return back()->with('success', 'Laporan komentar berhasil diabaikan dan ditandai bukan pelanggaran.');
            }
        } catch (\Exception $e) {
            Log::error('Failed dismiss comment report: ' . $e->getMessage());
        }

        return back()->with('error', 'Gagal mengabaikan laporan komentar.');
    }

    /**
     * Blokir Pengguna (Suspended/Banned)
     */
    public function blockUser(Request $request, $id)
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login')->with('error', 'Silakan masuk terlebih dahulu.');
        }

        $token = session('admin_token');
        $backendUrl = $this->getBackendUrl();

        try {
            $http = Http::timeout(6);
            if ($token) {
                $http = $http->withToken($token);
            }

            $response = $http->post("{$backendUrl}/api/admin/forum/users/{$id}/block");
            if ($response->successful()) {
                return back()->with('success', 'Pengguna berhasil diblokir dari platform MediGO.');
            }
        } catch (\Exception $e) {
            Log::error('Failed block user: ' . $e->getMessage());
        }

        return back()->with('error', 'Gagal memblokir pengguna.');
    }

    /**
     * Admin Laporkan Postingan Diskusi
     */
    public function adminReportDiscussion(Request $request, $id)
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login')->with('error', 'Silakan masuk terlebih dahulu.');
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $token = session('admin_token');
        $backendUrl = $this->getBackendUrl();

        try {
            $http = Http::timeout(6);
            if ($token) {
                $http = $http->withToken($token);
            }

            $response = $http->post("{$backendUrl}/api/admin/forum/discussions/{$id}/report", [
                'reason' => $request->input('reason'),
            ]);

            if ($response->successful()) {
                return back()->with('success', 'Postingan diskusi berhasil dilaporkan dan masuk ke antrean moderasi.');
            }
        } catch (\Exception $e) {
            Log::error('Failed reporting discussion: ' . $e->getMessage());
        }

        return back()->with('error', 'Gagal melaporkan postingan diskusi.');
    }

    /**
     * Admin Laporkan Komentar Diskusi
     */
    public function adminReportComment(Request $request, $id)
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login')->with('error', 'Silakan masuk terlebih dahulu.');
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $token = session('admin_token');
        $backendUrl = $this->getBackendUrl();

        try {
            $http = Http::timeout(6);
            if ($token) {
                $http = $http->withToken($token);
            }

            $response = $http->post("{$backendUrl}/api/admin/forum/comments/{$id}/report", [
                'reason' => $request->input('reason'),
            ]);

            if ($response->successful()) {
                return back()->with('success', 'Komentar diskusi berhasil dilaporkan dan masuk ke antrean moderasi.');
            }
        } catch (\Exception $e) {
            Log::error('Failed reporting comment: ' . $e->getMessage());
        }

        return back()->with('error', 'Gagal melaporkan komentar diskusi.');
    }
}

