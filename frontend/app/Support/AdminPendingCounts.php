<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Jumlah laporan/masukan yang menunggu tindakan admin, untuk badge di sidebar & lonceng notifikasi.
 * Diambil sekali per request dari backend (GET /api/admin/pending-counts).
 */
class AdminPendingCounts
{
    private static ?array $cache = null;

    public const EMPTY = [
        'discussion_reports' => 0,
        'discussion_comment_reports' => 0,
        'faskes_comment_reports' => 0,
        'faskes_feedbacks' => 0,
        'total' => 0,
    ];

    public static function get(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $token = session('admin_token');
        if (!$token) {
            return self::$cache = self::EMPTY;
        }

        try {
            $base = rtrim(str_replace('localhost', '127.0.0.1', config('services.medigo.url', env('MEDIGO_BE', 'http://127.0.0.1:8005'))), '/');
            $response = Http::timeout(3)->withToken($token)->acceptJson()->get("{$base}/api/admin/pending-counts");
            if ($response->successful()) {
                $data = (array) $response->json('data', []);
                $counts = [];
                foreach (self::EMPTY as $key => $default) {
                    $counts[$key] = max(0, (int) ($data[$key] ?? $default));
                }

                return self::$cache = $counts;
            }
        } catch (\Exception $e) {
            Log::warning('Admin pending counts fetch failed: ' . $e->getMessage());
        }

        return self::$cache = self::EMPTY;
    }
}
