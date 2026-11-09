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
                $feedbacks = $response->json()['data'] ?? [];
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
            'status' => 'required|in:pending,reviewed,applied,rejected',
            'admin_notes' => 'nullable|string|max:1000'
        ]);

        try {
            $backendUrl = $this->getBackendUrl();
            $response = Http::timeout(6)->withToken($token)->put("{$backendUrl}/api/admin/faskes-feedbacks/{$id}/status", [
                'status' => $request->status,
                'admin_notes' => $request->admin_notes
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
                $reports = $response->json()['data'] ?? [];
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
