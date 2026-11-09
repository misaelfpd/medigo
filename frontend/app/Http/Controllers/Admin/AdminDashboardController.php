<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login')->with('error', 'Silakan masuk terlebih dahulu untuk mengakses halaman ini.');
        }

        $rawBackendUrl = config('services.medigo.url', env('MEDIGO_BE', 'http://127.0.0.1:8005'));
        $backendUrl = rtrim(str_replace('localhost', '127.0.0.1', $rawBackendUrl), '/');
        $token = session('admin_token');

        $stats = [
            'total_users'            => 0,
            'user_growth_pct'        => 0,
            'total_faskes'           => 0,
            'faskes_growth_pct'      => 0,
            'total_discussions'      => 0,
            'discussions_growth_pct' => 0,
            'activities'             => [],
        ];

        $chartData = [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
            'values' => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
        ];

        try {
            $http = Http::timeout(5);
            if ($token) {
                $http = $http->withToken($token);
            }
            $response = $http->get("{$backendUrl}/api/admin/stats");
            if ($response->successful()) {
                $body = $response->json();
                $data = $body['data'] ?? [];

                if (isset($data['total_users']))            $stats['total_users']            = (int) $data['total_users'];
                if (isset($data['user_growth_pct']))        $stats['user_growth_pct']        = (float) $data['user_growth_pct'];
                if (isset($data['total_faskes']))           $stats['total_faskes']           = (int) $data['total_faskes'];
                if (isset($data['faskes_growth_pct']))      $stats['faskes_growth_pct']      = (float) $data['faskes_growth_pct'];
                if (isset($data['total_discussions']))      $stats['total_discussions']      = (int) $data['total_discussions'];
                if (isset($data['discussions_growth_pct'])) $stats['discussions_growth_pct'] = (float) $data['discussions_growth_pct'];
                if (!empty($data['activities']))            $stats['activities']             = $data['activities'];
                if (!empty($data['chart_monthly']))         $chartData['values']             = $data['chart_monthly'];
            }
        } catch (\Exception $e) {
            Log::warning('Failed to fetch dashboard stats from backend: ' . $e->getMessage());
        }

        return view('admin.dashboard', compact('stats', 'chartData'));
    }
}
