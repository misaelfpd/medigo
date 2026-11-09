<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdminDiscussionReportController extends Controller
{
    public function index()
    {
        $token = session('admin_token');
        if (!$token) {
            return redirect()->route('admin.login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $reports = [];
        try {
            $rawBackendUrl = config('services.medigo.url', env('MEDIGO_BE', 'http://127.0.0.1:8005'));
            $backendUrl = rtrim(str_replace('localhost', '127.0.0.1', $rawBackendUrl), '/');

            $response = Http::timeout(5)->withToken($token)->get("{$backendUrl}/api/admin/discussion-reports");
            
            if ($response->successful()) {
                $rawReports = $response->json()['data'] ?? [];
                $reports = collect($rawReports)->map(function ($r) {
                    $r = (array) $r;
                    $id = $r['id_laporan_diskusi'] ?? ($r['id'] ?? null);
                    $discId = $r['id_diskusi'] ?? ($r['discussion_id'] ?? null);
                    $reason = $r['alasan_laporan'] ?? ($r['reason'] ?? '');
                    $status = $r['discussion_status'] ?? ($r['status_diskusi'] ?? 'active');

                    return array_merge($r, [
                        'id' => $id,
                        'id_laporan_diskusi' => $id,
                        'discussion_id' => $discId,
                        'id_diskusi' => $discId,
                        'reason' => $reason,
                        'alasan_laporan' => $reason,
                        'discussion_status' => $status,
                    ]);
                })->all();
            }
        } catch (\Exception $e) {
            Log::error('Failed to fetch discussion reports: ' . $e->getMessage());
        }

        return view('admin.discussion_reports', compact('reports'));
    }

    public function takedown($id)
    {
        $token = session('admin_token');
        if (!$token) {
            return redirect()->route('admin.login')->with('error', 'Silakan login terlebih dahulu.');
        }

        try {
            $rawBackendUrl = config('services.medigo.url', env('MEDIGO_BE', 'http://127.0.0.1:8005'));
            $backendUrl = rtrim(str_replace('localhost', '127.0.0.1', $rawBackendUrl), '/');

            $response = Http::timeout(5)->withToken($token)->post("{$backendUrl}/api/admin/discussions/{$id}/takedown");
            
            if ($response->successful()) {
                return redirect()->route('admin.discussion-reports')->with('success', 'Diskusi berhasil di-takedown.');
            }
        } catch (\Exception $e) {
            Log::error('Failed to takedown discussion: ' . $e->getMessage());
        }

        return redirect()->route('admin.discussion-reports')->with('error', 'Gagal men-takedown diskusi.');
    }
}
