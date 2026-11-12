<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdminFaskesController extends Controller
{
    private function getBackendUrl()
    {
        $rawBackendUrl = config('services.medigo.url', env('MEDIGO_BE', 'http://127.0.0.1:8005'));
        return rtrim(str_replace('localhost', '127.0.0.1', $rawBackendUrl), '/');
    }

    public function index(Request $request)
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login')->with('error', 'Silakan masuk terlebih dahulu untuk mengakses direktori admin.');
        }

        $backendUrl = $this->getBackendUrl();
        $page = max(1, (int) $request->input('page', 1));
        $perPage = 15;
        $search = trim($request->input('search', ''));
        $filter = $request->input('filter', 'all'); // all, kelas_a, kelas_b, bpjs, non_bpjs
        $sort = $request->input('sort', 'default');
        $provinsi = trim($request->input('provinsi', ''));
        $kota = trim($request->input('kota', ''));
        $lokasi = trim($request->input('lokasi', ''));

        $faskesList = [];
        $paginationData = [
            'current_page' => $page,
            'last_page' => 1,
            'from' => 0,
            'to' => 0,
            'total' => 0,
            'per_page' => $perPage,
        ];

        $params = [
            'page' => $page,
            'limit' => $perPage,
        ];
        if (!empty($search)) {
            $params['search'] = $search;
        }
        if (!empty($provinsi)) {
            $params['provinsi'] = $provinsi;
        }
        if (!empty($kota)) {
            $params['kota'] = $kota;
        }
        if (!empty($lokasi)) {
            $params['lokasi'] = $lokasi;
        }
        if ($filter === 'kelas_a') {
            $params['jenis'] = 'hospital';
        } elseif ($filter === 'bpjs') {
            $params['bpjs'] = 1;
        } elseif ($filter === 'non_bpjs') {
            $params['bpjs'] = 0;
        }

        try {
            $response = Http::timeout(6)->get("{$backendUrl}/api/faskes", $params);
            if ($response->successful()) {
                $body = $response->json();
                $paginated = $body['data'] ?? [];
                $rawItems = $paginated['data'] ?? ($paginated ?? []);
                
                $paginationData['current_page'] = $paginated['current_page'] ?? $page;
                $paginationData['last_page'] = $paginated['last_page'] ?? 1;
                $paginationData['total'] = $paginated['total'] ?? count($rawItems);
                $paginationData['from'] = $paginated['from'] ?? (($page - 1) * $perPage + 1);
                $paginationData['to'] = $paginated['to'] ?? ($paginationData['from'] + count($rawItems) - 1);
                $paginationData['per_page'] = $paginated['per_page'] ?? $perPage;

                foreach ($rawItems as $item) {
                    $item = (array) $item;
                    $idNum = $item['id_faskes'] ?? ($item['id'] ?? 0);
                    $codeId = 'HOS-' . str_pad($idNum, 3, '0', STR_PAD_LEFT);
                    $kelas = $item['kelas'] ?? null;
                    if ($kelas === '' || $kelas === 'unknown') {
                        $kelas = null;
                    }

                    $isBpjs = null;
                    if (isset($item['_support_bpjs']) && $item['_support_bpjs'] !== null && $item['_support_bpjs'] !== '') {
                        $isBpjs = (int)$item['_support_bpjs'];
                    } elseif (isset($item['is_support_bpjs']) && $item['is_support_bpjs'] !== null && $item['is_support_bpjs'] !== '') {
                        $isBpjs = (int)$item['is_support_bpjs'];
                    }

                    $rating = (!empty($item['avg_rating']) && (float)$item['avg_rating'] > 0)
                        ? number_format((float)$item['avg_rating'], 1)
                        : null;

                    $updatedAt = !empty($item['updated_at'])
                        ? \Carbon\Carbon::parse($item['updated_at'])->translatedFormat('d M Y')
                        : null;

                    $tipe = $item['jenis_faskes'] ?? ($item['tipe'] ?? '');
                    $alamat = $item['alamat_faskes'] ?? ($item['alamat'] ?? '');

                    $faskesList[] = [
                        'id'                  => $idNum,
                        'id_faskes'           => $idNum,
                        'code_id'             => $codeId,
                        'nama_faskes'         => $item['nama_faskes'] ?? '',
                        'tipe'                => $tipe,
                        'jenis_faskes'        => $tipe,
                        'kelas'               => $kelas,
                        'is_support_bpjs'     => $isBpjs,
                        '_support_bpjs'       => $isBpjs,
                        'lokasi'              => $item['kota_kabupaten'] ?? '',
                        'kota_kabupaten'      => $item['kota_kabupaten'] ?? '',
                        'provinsi'            => $item['provinsi'] ?? '',
                        'alamat'              => $alamat,
                        'alamat_faskes'       => $alamat,
                        'kontak'              => $item['kontak'] ?? ($item['telepon'] ?? null),
                        'rating'              => $rating,
                        'updated_at_formatted'=> $updatedAt,
                        'latitude'            => $item['latitude'] ?? null,
                        'longitude'           => $item['longitude'] ?? null,
                        'link_maps'           => $item['link_maps'] ?? null,
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to fetch faskes for admin: ' . $e->getMessage());
        }

        // Complete List of Indonesian provinces for selection
        $provincesList = [
            'DKI Jakarta', 'Jawa Barat', 'Jawa Tengah', 'Jawa Timur', 'Banten', 
            'DI Yogyakarta', 'Bali', 'Nusa Tenggara Barat', 'Nusa Tenggara Timur',
            'Sumatera Utara', 'Sumatera Barat', 'Sumatera Selatan', 'Riau', 'Kepulauan Riau',
            'Jambi', 'Bengkulu', 'Lampung', 'Kepulauan Bangka Belitung', 'Aceh',
            'Kalimantan Barat', 'Kalimantan Tengah', 'Kalimantan Selatan', 'Kalimantan Timur', 'Kalimantan Utara',
            'Sulawesi Utara', 'Gorontalo', 'Sulawesi Tengah', 'Sulawesi Barat', 'Sulawesi Selatan', 'Sulawesi Tenggara',
            'Maluku', 'Maluku Utara', 'Papua', 'Papua Barat', 'Papua Barat Daya', 'Papua Tengah', 'Papua Selatan', 'Papua Pegunungan'
        ];

        // Global stats for filter pills counts
        $totalStats = $paginationData['total'];
        try {
            $http = Http::timeout(3);
            if ($token = session('admin_token')) {
                $http = $http->withToken($token);
            }
            $statsRes = $http->get("{$backendUrl}/api/admin/stats");
            if ($statsRes->successful()) {
                $totalStats = $statsRes->json()['data']['total_faskes'] ?? $totalStats;
            }
        } catch (\Exception $e) {}

        $counts = [
            'all'     => $totalStats,
            'kelas_a' => 0,
            'kelas_b' => 0,
            'bpjs'    => 0,
            'non_bpjs'=> 0,
        ];

        // Apply in-memory sort if requested
        if ($sort === 'rating_desc') {
            usort($faskesList, fn($a, $b) => (float)$b['rating'] <=> (float)$a['rating']);
        } elseif ($sort === 'rating_asc') {
            usort($faskesList, fn($a, $b) => (float)$a['rating'] <=> (float)$b['rating']);
        } elseif ($sort === 'name_asc') {
            usort($faskesList, fn($a, $b) => strcasecmp($a['nama_faskes'], $b['nama_faskes']));
        } elseif ($sort === 'name_desc') {
            usort($faskesList, fn($a, $b) => strcasecmp($b['nama_faskes'], $a['nama_faskes']));
        }

        // Fetch all fasilitas and spesialisasi master data for modals
        $allFasilitas = [];
        $allSpesialisasi = [];
        try {
            $fasRes = Http::timeout(4)->get("{$backendUrl}/api/fasilitas");
            if ($fasRes->successful()) {
                $allFasilitas = $fasRes->json()['data'] ?? [];
            }
            $specRes = Http::timeout(4)->get("{$backendUrl}/api/spesialisasi");
            if ($specRes->successful()) {
                $allSpesialisasi = $specRes->json()['data'] ?? [];
            }
        } catch (\Exception $e) {
            Log::warning('Failed to fetch facilities or specialties for admin: ' . $e->getMessage());
        }

        $totalCount = $paginationData['total'];

        return view('admin.faskes', compact('faskesList', 'totalCount', 'paginationData', 'counts', 'filter', 'search', 'sort', 'provinsi', 'kota', 'lokasi', 'provincesList', 'allFasilitas', 'allSpesialisasi'));
    }

    /**
     * Return single faskes data along with current facilities & specialties in JSON.
     * Used by AJAX for opening Edit Modal.
     */
    public function getFaskesJson($id)
    {
        if (!session('admin_logged_in')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $backendUrl = $this->getBackendUrl();
        try {
            $http = Http::timeout(6);
            if ($token = session('admin_token')) {
                $http = $http->withToken($token);
            }
            $response = $http->get("{$backendUrl}/api/faskes/{$id}");
            if ($response->successful()) {
                return response()->json($response->json());
            }
        } catch (\Exception $e) {
            Log::warning("Failed to fetch faskes json for id {$id}: " . $e->getMessage());
        }

        return response()->json(['status' => 'error', 'message' => 'Fasilitas kesehatan tidak ditemukan'], 404);
    }

    public function store(Request $request)
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login')->with('error', 'Silakan masuk terlebih dahulu untuk mengakses direktori admin.');
        }

        $request->validate([
            'nama_faskes'    => 'required|string|max:255',
            'tipe'           => 'required|string|max:100',
            'kelas'          => 'nullable|string|max:50',
            'kota_kabupaten' => 'required|string|max:100',
            'alamat'         => 'required|string',
            'is_support_bpjs'=> 'nullable|string',
            'link_maps'      => 'nullable|string',
            'kontak'         => 'nullable|string|max:50',
        ]);

        $backendUrl = $this->getBackendUrl();

        $kelasVal = $request->kelas;
        if ($kelasVal === '' || $kelasVal === 'unknown' || $kelasVal === 'Tidak Diketahui') {
            $kelasVal = null;
        }

        $bpjsVal = $request->is_support_bpjs;
        $isBpjs = null;
        if ($bpjsVal === '1' || $bpjsVal === '0') {
            $isBpjs = (int)$bpjsVal;
        }

        $fasilitas = $request->input('fasilitas', []);
        if (is_string($fasilitas)) {
            $fasilitas = json_decode($fasilitas, true) ?: explode(',', $fasilitas);
        }

        $spesialisasi = $request->input('spesialisasi', []);
        if (is_string($spesialisasi)) {
            $spesialisasi = json_decode($spesialisasi, true) ?: explode(',', $spesialisasi);
        }

        try {
            $http = Http::timeout(6);
            if ($token = session('admin_token')) {
                $http = $http->withToken($token);
            }
            $http->post("{$backendUrl}/api/faskes", [
                'nama_faskes'    => $request->nama_faskes,
                'jenis_faskes'   => $request->tipe ?? $request->jenis_faskes,
                'tipe'           => $request->tipe ?? $request->jenis_faskes,
                'kelas'          => $kelasVal,
                'kota_kabupaten' => $request->kota_kabupaten,
                'provinsi'       => $request->provinsi ?? 'DKI Jakarta',
                'alamat_faskes'  => $request->alamat ?? $request->alamat_faskes,
                'alamat'         => $request->alamat ?? $request->alamat_faskes,
                '_support_bpjs'  => $isBpjs,
                'is_support_bpjs'=> $isBpjs,
                'link_maps'      => $request->link_maps,
                'latitude'       => $request->latitude,
                'longitude'      => $request->longitude,
                'kontak'         => $request->kontak,
                'telepon'        => $request->kontak,
                'fasilitas'      => $fasilitas,
                'spesialisasi'   => $spesialisasi,
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to store faskes to backend: ' . $e->getMessage());
        }

        return redirect()->route('admin.faskes')->with('success', 'Fasilitas kesehatan baru berhasil ditambahkan ke direktori!');
    }

    public function update(Request $request, $id)
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login')->with('error', 'Silakan masuk terlebih dahulu untuk mengakses direktori admin.');
        }

        $request->validate([
            'nama_faskes'    => 'required|string|max:255',
            'tipe'           => 'required|string|max:100',
            'kelas'          => 'nullable|string|max:50',
            'kota_kabupaten' => 'required|string|max:100',
            'alamat'         => 'required|string',
            'is_support_bpjs'=> 'nullable|string',
            'link_maps'      => 'nullable|string',
            'kontak'         => 'nullable|string|max:50',
        ]);

        $backendUrl = $this->getBackendUrl();

        $kelasVal = $request->kelas;
        if ($kelasVal === '' || $kelasVal === 'unknown' || $kelasVal === 'Tidak Diketahui') {
            $kelasVal = null;
        }

        $bpjsVal = $request->is_support_bpjs;
        $isBpjs = null;
        if ($bpjsVal === '1' || $bpjsVal === '0') {
            $isBpjs = (int)$bpjsVal;
        }

        $fasilitas = $request->input('fasilitas', []);
        if (is_string($fasilitas)) {
            $fasilitas = json_decode($fasilitas, true) ?: explode(',', $fasilitas);
        }

        $spesialisasi = $request->input('spesialisasi', []);
        if (is_string($spesialisasi)) {
            $spesialisasi = json_decode($spesialisasi, true) ?: explode(',', $spesialisasi);
        }

        try {
            $http = Http::timeout(6);
            if ($token = session('admin_token')) {
                $http = $http->withToken($token);
            }
            $http->put("{$backendUrl}/api/faskes/{$id}", [
                'nama_faskes'    => $request->nama_faskes,
                'jenis_faskes'   => $request->tipe ?? $request->jenis_faskes,
                'tipe'           => $request->tipe ?? $request->jenis_faskes,
                'kelas'          => $kelasVal,
                'kota_kabupaten' => $request->kota_kabupaten,
                'provinsi'       => $request->provinsi ?? 'DKI Jakarta',
                'alamat_faskes'  => $request->alamat ?? $request->alamat_faskes,
                'alamat'         => $request->alamat ?? $request->alamat_faskes,
                '_support_bpjs'  => $isBpjs,
                'is_support_bpjs'=> $isBpjs,
                'link_maps'      => $request->link_maps,
                'latitude'       => $request->latitude,
                'longitude'      => $request->longitude,
                'kontak'         => $request->kontak,
                'telepon'        => $request->kontak,
                'fasilitas'      => $fasilitas,
                'spesialisasi'   => $spesialisasi,
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to update faskes in backend: ' . $e->getMessage());
        }

        return redirect()->route('admin.faskes')->with('success', 'Data fasilitas kesehatan berhasil diperbarui!');
    }

    public function destroy($id)
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login')->with('error', 'Silakan masuk terlebih dahulu untuk mengakses direktori admin.');
        }

        $backendUrl = $this->getBackendUrl();

        try {
            $http = Http::timeout(6);
            if ($token = session('admin_token')) {
                $http = $http->withToken($token);
            }
            $http->delete("{$backendUrl}/api/faskes/{$id}");
        } catch (\Exception $e) {
            Log::warning('Failed to delete faskes in backend: ' . $e->getMessage());
        }

        return redirect()->route('admin.faskes')->with('success', 'Rumah Sakit berhasil dihapus dari direktori.');
    }

    /**
     * Tambah jenis fasilitas baru via Admin (AJAX / Form)
     */
    public function storeFasilitas(Request $request)
    {
        if (!session('admin_logged_in')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $request->validate([
            'nama_fasilitas' => 'required|string|max:150',
        ]);

        $backendUrl = $this->getBackendUrl();

        try {
            $http = Http::timeout(6);
            if ($token = session('admin_token')) {
                $http = $http->withToken($token);
            }

            $response = $http->post("{$backendUrl}/api/fasilitas", [
                'nama_fasilitas' => trim($request->nama_fasilitas),
            ]);

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json([
                'status' => 'error',
                'message' => $response->json()['message'] ?? 'Gagal menambahkan fasilitas di backend',
            ], $response->status());
        } catch (\Exception $e) {
            Log::error('Error storeFasilitas: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan sistem saat menghubungi server backend: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Tambah jenis spesialisasi baru via Admin (AJAX / Form)
     */
    public function storeSpesialisasi(Request $request)
    {
        if (!session('admin_logged_in')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $request->validate([
            'nama_spesialisasi' => 'required|string|max:100',
        ]);

        $backendUrl = $this->getBackendUrl();

        try {
            $http = Http::timeout(6);
            if ($token = session('admin_token')) {
                $http = $http->withToken($token);
            }

            $response = $http->post("{$backendUrl}/api/spesialisasi", [
                'nama_spesialisasi' => trim($request->nama_spesialisasi),
            ]);

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json([
                'status' => 'error',
                'message' => $response->json()['message'] ?? 'Gagal menambahkan spesialisasi di backend',
            ], $response->status());
        } catch (\Exception $e) {
            Log::error('Error storeSpesialisasi: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan sistem saat menghubungi server backend: ' . $e->getMessage(),
            ], 500);
        }
    }
}
