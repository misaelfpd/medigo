<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdminFasilitasController extends Controller
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
        $fasilitas = [];
        $loadFailed = false;

        try {
            $response = Http::timeout(6)->acceptJson()->get("{$backendUrl}/api/fasilitas", [
                'search' => $search ?: null,
            ]);
            if ($response->successful()) {
                $fasilitas = $response->json('data') ?? [];
            } else {
                $loadFailed = true;
            }
        } catch (\Exception $e) {
            $loadFailed = true;
            Log::warning('Failed fetching fasilitas for admin: ' . $e->getMessage());
        }

        return view('admin.fasilitas', compact('fasilitas', 'search', 'loadFailed'));
    }

    public function store(Request $request)
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login')->with('error', 'Silakan masuk terlebih dahulu.');
        }

        $request->validate([
            'nama_fasilitas' => 'required|string|max:150',
        ]);

        $token = session('admin_token');
        $backendUrl = $this->getBackendUrl();

        try {
            $http = Http::timeout(6)->acceptJson();
            if ($token) {
                $http = $http->withToken($token);
            }
            $response = $http->post("{$backendUrl}/api/admin/fasilitas", [
                'nama_fasilitas' => trim($request->input('nama_fasilitas')),
            ]);

            if ($response->successful()) {
                return redirect()->route('admin.fasilitas')->with('success', 'Fasilitas penunjang berhasil ditambahkan.');
            }

            $errMsg = $response->json('message') ?? 'Gagal menambahkan fasilitas penunjang.';
            return redirect()->back()->withInput()->with('error', $errMsg);
        } catch (\Exception $e) {
            Log::error('Error storing fasilitas: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Terjadi kesalahan sistem saat menyimpan fasilitas.');
        }
    }

    public function update(Request $request, $id)
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login')->with('error', 'Silakan masuk terlebih dahulu.');
        }

        $request->validate([
            'nama_fasilitas' => 'required|string|max:150',
        ]);

        $token = session('admin_token');
        $backendUrl = $this->getBackendUrl();

        try {
            $http = Http::timeout(6)->acceptJson();
            if ($token) {
                $http = $http->withToken($token);
            }
            $response = $http->put("{$backendUrl}/api/admin/fasilitas/{$id}", [
                'nama_fasilitas' => trim($request->input('nama_fasilitas')),
            ]);

            if ($response->successful()) {
                return redirect()->route('admin.fasilitas')->with('success', 'Fasilitas penunjang berhasil diperbarui.');
            }

            $errMsg = $response->json('message') ?? 'Gagal memperbarui fasilitas penunjang.';
            return redirect()->back()->withInput()->with('error', $errMsg);
        } catch (\Exception $e) {
            Log::error('Error updating fasilitas: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Terjadi kesalahan sistem saat memperbarui fasilitas.');
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
            $response = $http->delete("{$backendUrl}/api/admin/fasilitas/{$id}");

            if ($response->successful()) {
                return redirect()->route('admin.fasilitas')->with('success', 'Fasilitas penunjang berhasil dihapus.');
            }

            $errMsg = $response->json('message') ?? 'Gagal menghapus fasilitas penunjang.';
            return redirect()->back()->with('error', $errMsg);
        } catch (\Exception $e) {
            Log::error('Error deleting fasilitas: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan sistem saat menghapus fasilitas.');
        }
    }
}
