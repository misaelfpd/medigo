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
        $tipe = trim($request->input('tipe', ''));
        $kelas = trim($request->input('kelas', ''));
        $bpjs = $request->input('bpjs', '');

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
        if (!empty($tipe)) {
            $params['tipe'] = $tipe;
        }
        if (!empty($kelas)) {
            $params['kelas'] = $kelas;
        }
        if ($bpjs !== '' && $bpjs !== null) {
            $params['_support_bpjs'] = $bpjs;
        }
        // Chip filter sesuai desain: Kelas A, Kelas B, Menerima BPJS, Non BPJS
        if ($filter === 'kelas_a') {
            $params['kelas'] = 'A';
        } elseif ($filter === 'kelas_b') {
            $params['kelas'] = 'B';
        } elseif ($filter === 'bpjs') {
            $params['_support_bpjs'] = 1;
        } elseif ($filter === 'non_bpjs') {
            $params['_support_bpjs'] = 0;
        } else {
            $filter = 'all';
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

        // Ambil opsi filter dinamis dari backend (kota, provinsi, jenis faskes, kelas) tanpa hardcoding
        $citiesList = [];
        $provincesList = [];
        $typesList = [];
        $kelasList = [];
        try {
            $filterRes = Http::timeout(4)->acceptJson()->get("{$backendUrl}/api/faskes/filter-options");
            if ($filterRes->successful()) {
                $fData = $filterRes->json('data') ?? [];
                $citiesList = $fData['kota'] ?? [];
                $provincesList = $fData['provinsi'] ?? [];
                $typesList = $fData['jenis_faskes'] ?? [];
                $kelasList = $fData['kelas'] ?? [];
            }
        } catch (\Exception $e) {
            Log::warning('Failed to fetch filter options for admin: ' . $e->getMessage());
        }

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

        return view('admin.faskes', compact(
            'faskesList', 'totalCount', 'paginationData', 'counts', 'filter', 'search', 'sort',
            'provinsi', 'kota', 'lokasi', 'tipe', 'kelas', 'bpjs',
            'provincesList', 'citiesList', 'typesList', 'kelasList',
            'allFasilitas', 'allSpesialisasi'
        ));
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

    /**
     * Data master untuk form faskes: daftar fasilitas, spesialisasi, dan provinsi.
     */
    private function formMasterData(): array
    {
        $backendUrl = $this->getBackendUrl();

        $allFasilitas = [];
        $allSpesialisasi = [];
        $provincesList = [];
        try {
            $fasRes = Http::timeout(4)->acceptJson()->get("{$backendUrl}/api/fasilitas");
            if ($fasRes->successful()) {
                $allFasilitas = $fasRes->json('data') ?? [];
            }
            $specRes = Http::timeout(4)->acceptJson()->get("{$backendUrl}/api/spesialisasi");
            if ($specRes->successful()) {
                $allSpesialisasi = $specRes->json('data') ?? [];
            }
            $filterRes = Http::timeout(4)->acceptJson()->get("{$backendUrl}/api/faskes/filter-options");
            if ($filterRes->successful()) {
                $provincesList = $filterRes->json('data.provinsi') ?? [];
            }
        } catch (\Exception $e) {
            Log::warning('Failed to fetch facilities, specialties, or provinces for faskes form: ' . $e->getMessage());
        }

        return compact('allFasilitas', 'allSpesialisasi', 'provincesList');
    }

    /**
     * Halaman Tambah Faskes Baru (Tab Informasi, Fasilitas, Spesialisasi, Foto).
     */
    public function create()
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login')->with('error', 'Silakan masuk terlebih dahulu untuk mengakses direktori admin.');
        }

        return view('admin.faskes_create', $this->formMasterData() + [
            'faskes' => null,
            'selectedFasilitas' => [],
            'selectedSpesialisasi' => [],
        ]);
    }

    /**
     * Halaman Ubah Data Faskes (memakai form yang sama dengan Tambah Faskes).
     */
    public function edit($id)
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login')->with('error', 'Silakan masuk terlebih dahulu untuk mengakses direktori admin.');
        }

        try {
            $response = $this->adminHttp()->get($this->getBackendUrl() . "/api/faskes/{$id}");
            if (!$response->successful()) {
                return redirect()->route('admin.faskes')->with('error', 'Fasilitas kesehatan tidak ditemukan.');
            }
        } catch (\Exception $e) {
            Log::warning("Failed to fetch faskes {$id} for edit: " . $e->getMessage());

            return redirect()->route('admin.faskes')->with('error', 'Server backend tidak dapat dihubungi.');
        }

        $data = $response->json('data') ?? [];
        $faskes = $data['faskes'] ?? $data;
        if (empty($faskes['id_faskes'])) {
            return redirect()->route('admin.faskes')->with('error', 'Fasilitas kesehatan tidak ditemukan.');
        }

        $selectedFasilitas = collect($data['fasilitas'] ?? $faskes['fasilitas'] ?? [])
            ->map(fn ($item) => is_array($item) ? ($item['nama_fasilitas'] ?? null) : $item)
            ->filter()->values()->all();
        $selectedSpesialisasi = collect($data['spesialisasi'] ?? $faskes['spesialisasi'] ?? [])
            ->map(fn ($item) => is_array($item) ? ($item['nama_spesialisasi'] ?? null) : $item)
            ->filter()->values()->all();

        return view('admin.faskes_create', $this->formMasterData() + compact('faskes', 'selectedFasilitas', 'selectedSpesialisasi'));
    }

    public function store(Request $request)
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login')->with('error', 'Silakan masuk terlebih dahulu untuk mengakses direktori admin.');
        }

        $request->validate([
            'nama_faskes'    => 'required|string|max:255',
            'tipe'           => 'nullable|string|max:100',
            'jenis_faskes'   => 'nullable|string|max:100',
            'kelas'          => 'nullable|in:A,B,C,D',
            'kota_kabupaten' => 'required|string|max:100',
            'provinsi'       => 'required|string|max:100',
            'alamat'         => 'required|string',
            'is_support_bpjs'=> 'nullable|in:0,1',
            'link_maps'      => 'nullable|string',
            'kontak'         => 'nullable|string|max:50',
            'telepon'        => 'nullable|string|max:50',
            // Foto (opsional) - dikonversi ke WebP oleh backend
            'banner'         => 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120',
            'thumbnail'      => 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120',
            'galeri'         => 'nullable|array|max:20',
            'galeri.*'       => 'image|mimes:jpeg,jpg,png,webp|max:5120',
        ], [
            'banner.max' => 'Ukuran foto banner maksimal 5MB.',
            'thumbnail.max' => 'Ukuran foto thumbnail maksimal 5MB.',
            'galeri.*.max' => 'Ukuran setiap foto galeri maksimal 5MB.',
            'galeri.max' => 'Maksimal 20 foto galeri.',
        ]);

        $backendUrl = $this->getBackendUrl();

        $tipeVal = $request->input('tipe', $request->input('jenis_faskes', 'Rumah Sakit')) ?: 'Rumah Sakit';
        $kontakVal = $request->input('telepon', $request->input('kontak'));

        $kelasVal = $request->kelas;
        if ($kelasVal === '' || $kelasVal === 'unknown' || $kelasVal === 'Tidak Diketahui') {
            $kelasVal = null;
        }

        $bpjsVal = $request->is_support_bpjs;
        $isBpjs = null;
        if ($bpjsVal === '1' || $bpjsVal === 1 || $bpjsVal === 'true' || $bpjsVal === true) {
            $isBpjs = 1;
        } elseif ($bpjsVal === '0' || $bpjsVal === 0 || $bpjsVal === 'false' || $bpjsVal === false) {
            $isBpjs = 0;
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
            $response = $http->acceptJson()->post("{$backendUrl}/api/faskes", [
                'nama_faskes'    => $request->nama_faskes,
                'jenis_faskes'   => $tipeVal,
                'tipe'           => $tipeVal,
                'kelas'          => $kelasVal,
                'kota_kabupaten' => $request->kota_kabupaten,
                'provinsi'       => $request->provinsi,
                'alamat_faskes'  => $request->alamat ?? $request->alamat_faskes,
                'alamat'         => $request->alamat ?? $request->alamat_faskes,
                '_support_bpjs'  => $isBpjs,
                'is_support_bpjs'=> $isBpjs,
                'link_maps'      => $request->link_maps,
                'latitude'       => $request->latitude,
                'longitude'      => $request->longitude,
                'kontak'         => $kontakVal,
                'telepon'        => $kontakVal,
                'fasilitas'      => $fasilitas,
                'spesialisasi'   => $spesialisasi,
            ]);
            if (! $response->successful()) {
                return back()->withInput($request->except(['banner', 'thumbnail', 'galeri']))
                    ->with('error', $this->backendErrorMessage($response, 'Gagal menyimpan faskes ke server.'));
            }

            $newId = $response->json('data.id') ?? $response->json('data.id_faskes');
            $photoErrors = $newId ? $this->uploadPhotos((int) $newId, $request) : [];
        } catch (\Exception $e) {
            Log::warning('Failed to store faskes to backend: ' . $e->getMessage());

            return back()->withInput($request->except(['banner', 'thumbnail', 'galeri']))
                ->with('error', 'Server backend tidak dapat dihubungi. Data belum tersimpan.');
        }

        if (! empty($photoErrors)) {
            return redirect()->route('admin.faskes.photos', $newId)
                ->with('error', 'Faskes tersimpan, tetapi sebagian foto gagal diunggah: ' . implode(' ', $photoErrors));
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
            'kelas'          => 'nullable|in:A,B,C,D',
            'kota_kabupaten' => 'required|string|max:100',
            'provinsi'       => 'required|string|max:100',
            'alamat'         => 'required|string',
            'is_support_bpjs'=> 'nullable|in:0,1',
            'link_maps'      => 'nullable|url',
            'kontak'         => 'nullable|string|max:50',
            'telepon'        => 'nullable|string|max:50',
            'latitude'       => 'nullable|numeric|between:-90,90',
            'longitude'      => 'nullable|numeric|between:-180,180',
        ], [
            'kelas.in' => 'Kelas rumah sakit harus A, B, C, atau D.',
            'link_maps.url' => 'Link Google Maps tidak valid.',
        ]);
        $kontakVal = $request->input('telepon', $request->input('kontak'));

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
            $response = $http->acceptJson()->put("{$backendUrl}/api/faskes/{$id}", [
                'nama_faskes'    => $request->nama_faskes,
                'jenis_faskes'   => $request->tipe ?? $request->jenis_faskes,
                'tipe'           => $request->tipe ?? $request->jenis_faskes,
                'kelas'          => $kelasVal,
                'kota_kabupaten' => $request->kota_kabupaten,
                'provinsi'       => $request->provinsi,
                'alamat_faskes'  => $request->alamat ?? $request->alamat_faskes,
                'alamat'         => $request->alamat ?? $request->alamat_faskes,
                '_support_bpjs'  => $isBpjs,
                'is_support_bpjs'=> $isBpjs,
                'link_maps'      => $request->link_maps,
                'latitude'       => $request->latitude,
                'longitude'      => $request->longitude,
                'kontak'         => $kontakVal,
                'telepon'        => $kontakVal,
                'fasilitas'      => $fasilitas,
                'spesialisasi'   => $spesialisasi,
            ]);
            if (! $response->successful()) {
                return back()->withInput()
                    ->with('error', $this->backendErrorMessage($response, 'Gagal memperbarui data faskes.'));
            }
        } catch (\Exception $e) {
            Log::warning('Failed to update faskes in backend: ' . $e->getMessage());

            return back()->withInput()->with('error', 'Server backend tidak dapat dihubungi. Perubahan belum tersimpan.');
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
            $response = $http->acceptJson()->delete("{$backendUrl}/api/faskes/{$id}");
            if (! $response->successful()) {
                return redirect()->route('admin.faskes')
                    ->with('error', $this->backendErrorMessage($response, 'Gagal menghapus faskes.'));
            }
        } catch (\Exception $e) {
            Log::warning('Failed to delete faskes in backend: ' . $e->getMessage());

            return redirect()->route('admin.faskes')->with('error', 'Server backend tidak dapat dihubungi. Faskes belum terhapus.');
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

    private function adminHttp()
    {
        $http = Http::timeout(30)->acceptJson();
        if ($token = session('admin_token')) {
            $http = $http->withToken($token);
        }

        return $http;
    }

    /**
     * Ambil pesan error yang ramah dari respons backend (termasuk error validasi).
     */
    private function backendErrorMessage($response, string $fallback): string
    {
        if ($response->status() === 401 || $response->status() === 403) {
            return 'Sesi admin tidak valid atau tidak punya akses. Silakan masuk ulang.';
        }

        $errors = $response->json('errors');
        if (is_array($errors) && ! empty($errors)) {
            return collect($errors)->flatten()->filter()->take(3)->implode(' ');
        }

        return $response->json('message') ?: $fallback;
    }

    /**
     * Unggah foto banner / thumbnail / galeri ke backend (backend mengonversi ke WebP).
     *
     * @return array<int, string> daftar pesan gagal
     */
    private function uploadPhotos(int $faskesId, Request $request): array
    {
        $backendUrl = $this->getBackendUrl();
        $errors = [];

        foreach (['banner', 'thumbnail'] as $tipe) {
            $file = $request->file($tipe);
            if (! $file || ! $file->isValid()) {
                continue;
            }
            $res = $this->adminHttp()
                ->attach('foto', file_get_contents($file->getPathname()), $file->getClientOriginalName())
                ->post("{$backendUrl}/api/faskes/{$faskesId}/foto", ['tipe' => $tipe]);
            if (! $res->successful()) {
                $errors[] = ucfirst($tipe) . ': ' . $this->backendErrorMessage($res, 'gagal diunggah.');
            }
        }

        $galeri = array_filter((array) $request->file('galeri', []), fn ($f) => $f && $f->isValid());
        if (! empty($galeri)) {
            $http = $this->adminHttp();
            foreach ($galeri as $file) {
                $http = $http->attach('foto[]', file_get_contents($file->getPathname()), $file->getClientOriginalName());
            }
            $res = $http->post("{$backendUrl}/api/faskes/{$faskesId}/foto", ['tipe' => 'galeri']);
            if (! $res->successful()) {
                $errors[] = 'Galeri: ' . $this->backendErrorMessage($res, 'gagal diunggah.');
            }
        }

        return $errors;
    }

    /**
     * Halaman kelola foto 1 faskes: thumbnail, banner, galeri.
     */
    public function photos($id)
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login')->with('error', 'Silakan masuk terlebih dahulu untuk mengakses direktori admin.');
        }

        $backendUrl = $this->getBackendUrl();
        $faskes = null;
        $photos = ['thumbnail' => null, 'banner' => null, 'galeri' => []];

        try {
            $res = Http::timeout(8)->get("{$backendUrl}/api/faskes/{$id}");
            if ($res->successful()) {
                $faskes = $res->json('data.faskes');
                $photos = array_merge($photos, (array) ($res->json('data.foto') ?? []));
            }
        } catch (\Exception $e) {
            Log::warning('Failed to fetch faskes photos: ' . $e->getMessage());
        }

        if (! $faskes) {
            return redirect()->route('admin.faskes')->with('error', 'Faskes tidak ditemukan atau server tidak dapat dihubungi.');
        }

        return view('admin.faskes_photos', compact('faskes', 'photos'));
    }

    public function uploadPhoto(Request $request, $id)
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login')->with('error', 'Silakan masuk terlebih dahulu untuk mengakses direktori admin.');
        }

        $request->validate([
            'banner'    => 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120',
            'thumbnail' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120',
            'galeri'    => 'nullable|array|max:20',
            'galeri.*'  => 'image|mimes:jpeg,jpg,png,webp|max:5120',
        ], [
            'banner.max' => 'Ukuran foto banner maksimal 5MB.',
            'thumbnail.max' => 'Ukuran foto thumbnail maksimal 5MB.',
            'galeri.*.max' => 'Ukuran setiap foto galeri maksimal 5MB.',
        ]);

        if (! $request->hasFile('banner') && ! $request->hasFile('thumbnail') && ! $request->hasFile('galeri')) {
            return back()->with('error', 'Pilih minimal 1 foto untuk diunggah.');
        }

        try {
            $errors = $this->uploadPhotos((int) $id, $request);
        } catch (\Exception $e) {
            Log::warning('Failed to upload faskes photo: ' . $e->getMessage());

            return back()->with('error', 'Server backend tidak dapat dihubungi.');
        }

        return empty($errors)
            ? back()->with('success', 'Foto berhasil diunggah.')
            : back()->with('error', implode(' ', $errors));
    }

    public function deletePhoto($id, $fotoId)
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login')->with('error', 'Silakan masuk terlebih dahulu untuk mengakses direktori admin.');
        }

        try {
            $res = $this->adminHttp()->delete($this->getBackendUrl() . "/api/faskes/{$id}/foto/{$fotoId}");

            return $res->successful()
                ? back()->with('success', 'Foto berhasil dihapus.')
                : back()->with('error', $this->backendErrorMessage($res, 'Gagal menghapus foto.'));
        } catch (\Exception $e) {
            return back()->with('error', 'Server backend tidak dapat dihubungi.');
        }
    }

    /**
     * Jadikan foto galeri sebagai thumbnail atau banner.
     */
    public function setPhotoAs(Request $request, $id, $fotoId)
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login')->with('error', 'Silakan masuk terlebih dahulu untuk mengakses direktori admin.');
        }

        $request->validate(['tipe' => 'required|in:thumbnail,banner']);

        try {
            $res = $this->adminHttp()->post($this->getBackendUrl() . "/api/faskes/{$id}/foto/{$fotoId}/jadikan", [
                'tipe' => $request->input('tipe'),
            ]);

            return $res->successful()
                ? back()->with('success', 'Foto dijadikan ' . $request->input('tipe') . '.')
                : back()->with('error', $this->backendErrorMessage($res, 'Gagal mengubah foto.'));
        } catch (\Exception $e) {
            return back()->with('error', 'Server backend tidak dapat dihubungi.');
        }
    }
}
