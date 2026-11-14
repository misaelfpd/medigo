<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdminSpesialisasiController extends Controller
{
    private function getBackendUrl()
    {
        $rawBackendUrl = config('services.medigo.url', env('MEDIGO_BE', 'http://127.0.0.1:8005'));
        return rtrim(str_replace('localhost', '127.0.0.1', $rawBackendUrl), '/');
    }

    public function index(Request $request)
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login')->with('error', 'Silakan masuk terlebih dahulu.');
        }

        $search = trim((string) $request->input('search', ''));
        $backendUrl = $this->getBackendUrl();
        $spesialisasi = [];
        $loadFailed = false;

        try {
            $response = Http::timeout(6)->acceptJson()->get("{$backendUrl}/api/spesialisasi", [
                'search' => $search ?: null,
            ]);
            if ($response->successful()) {
                $spesialisasi = $response->json('data') ?? [];
            } else {
                $loadFailed = true;
            }
        } catch (\Exception $e) {
            $loadFailed = true;
            Log::warning('Failed fetching spesialisasi for admin: ' . $e->getMessage());
        }

        return view('admin.spesialisasi', compact('spesialisasi', 'search', 'loadFailed'));
    }

    public function store(Request $request)
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login')->with('error', 'Silakan masuk terlebih dahulu.');
        }

        $request->validate([
            'nama_spesialisasi' => 'required|string|max:100',
        ]);

        $token = session('admin_token');
        $backendUrl = $this->getBackendUrl();

        try {
            $http = Http::timeout(6)->acceptJson();
            if ($token) {
                $http = $http->withToken($token);
            }
            $response = $http->post("{$backendUrl}/api/admin/spesialisasi", [
                'nama_spesialisasi' => trim($request->input('nama_spesialisasi')),
            ]);

            if ($response->successful()) {
                return redirect()->route('admin.spesialisasi')->with('success', 'Spesialisasi medis berhasil ditambahkan.');
            }

            $errMsg = $response->json('message') ?? 'Gagal menambahkan spesialisasi medis.';
            return redirect()->back()->withInput()->with('error', $errMsg);
        } catch (\Exception $e) {
            Log::error('Error storing spesialisasi: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Terjadi kesalahan sistem saat menyimpan spesialisasi.');
        }
    }

    public function update(Request $request, $id)
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login')->with('error', 'Silakan masuk terlebih dahulu.');
        }

        $request->validate([
            'nama_spesialisasi' => 'required|string|max:100',
        ]);

        $token = session('admin_token');
        $backendUrl = $this->getBackendUrl();

        try {
            $http = Http::timeout(6)->acceptJson();
            if ($token) {
                $http = $http->withToken($token);
            }
            $response = $http->put("{$backendUrl}/api/admin/spesialisasi/{$id}", [
                'nama_spesialisasi' => trim($request->input('nama_spesialisasi')),
            ]);

            if ($response->successful()) {
                return redirect()->route('admin.spesialisasi')->with('success', 'Spesialisasi medis berhasil diperbarui.');
            }

            $errMsg = $response->json('message') ?? 'Gagal memperbarui spesialisasi medis.';
            return redirect()->back()->withInput()->with('error', $errMsg);
        } catch (\Exception $e) {
            Log::error('Error updating spesialisasi: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Terjadi kesalahan sistem saat memperbarui spesialisasi.');
        }
    }

    public function destroy($id)
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login')->with('error', 'Silakan masuk terlebih dahulu.');
        }

        $token = session('admin_token');
        $backendUrl = $this->getBackendUrl();

        try {
            $http = Http::timeout(6)->acceptJson();
            if ($token) {
                $http = $http->withToken($token);
            }
            $response = $http->delete("{$backendUrl}/api/admin/spesialisasi/{$id}");

            if ($response->successful()) {
                return redirect()->route('admin.spesialisasi')->with('success', 'Spesialisasi medis berhasil dihapus.');
            }

            $errMsg = $response->json('message') ?? 'Gagal menghapus spesialisasi medis.';
            return redirect()->back()->with('error', $errMsg);
        } catch (\Exception $e) {
            Log::error('Error deleting spesialisasi: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan sistem saat menghapus spesialisasi.');
        }
    }
}
