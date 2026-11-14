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

        $currentYear = (int) now()->year;
        $year = (int) $request->query('year', $currentYear);
        if ($year < $currentYear - 1 || $year > $currentYear) {
            $year = $currentYear;
        }

        $stats = [
            'total_users'            => 0,
            'user_growth_pct'        => 0,
            'new_users_30d'          => 0,
            'total_faskes'           => 0,
            'faskes_growth_pct'      => 0,
            'new_faskes_30d'         => 0,
            'total_discussions'      => 0,
            'discussions_growth_pct' => 0,
            'new_discussions_30d'    => 0,
            'activities'             => [],
        ];

        $chartData = [
            'year'   => $year,
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
            'values' => array_fill(0, 12, null),
        ];
        $statsLoaded = false;

        try {
            $http = Http::timeout(5)->acceptJson();
            if ($token) {
                $http = $http->withToken($token);
            }
            $response = $http->get("{$backendUrl}/api/admin/stats", ['year' => $year]);
            if ($response->successful()) {
                $data = $response->json('data', []);
                $statsLoaded = true;

                foreach (['total_users', 'total_faskes', 'total_discussions', 'new_users_30d', 'new_faskes_30d', 'new_discussions_30d'] as $key) {
                    if (isset($data[$key])) $stats[$key] = (int) $data[$key];
                }
                foreach (['user_growth_pct', 'faskes_growth_pct', 'discussions_growth_pct'] as $key) {
                    if (isset($data[$key])) $stats[$key] = (float) $data[$key];
                }
                if (!empty($data['activities'])) $stats['activities'] = $data['activities'];
                if (isset($data['chart_monthly']) && is_array($data['chart_monthly'])) {
                    $chartData['values'] = array_map(
                        fn ($v) => $v === null ? null : (int) $v,
                        array_slice(array_pad($data['chart_monthly'], 12, null), 0, 12)
                    );
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to fetch dashboard stats from backend: ' . $e->getMessage());
        }

        return view('admin.dashboard', compact('stats', 'chartData', 'statsLoaded', 'currentYear'));
    }
}
