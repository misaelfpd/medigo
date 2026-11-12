<?php

namespace App\Http\Controllers;

use App\Models\Faskes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FaskesFeatureController extends Controller
{
    /**
     * Ambil daftar fasilitas dan spesialisasi yang dimiliki oleh suatu faskes sekaligus.
     * Cocok dipanggil saat membuka halaman detail faskes.
     *
     * @param int|string $id
     * @return JsonResponse
     */
    public function getFacilitiesAndSpecialties($id): JsonResponse
    {
        $faskes = DB::table('faskes')
            ->select('id_faskes', 'nama_faskes', 'jenis_faskes')
            ->where('id_faskes', $id)
            ->first();

        if (! $faskes) {
            return response()->json([
                'status' => 'error',
                'message' => 'Fasilitas kesehatan tidak ditemukan',
            ], 404);
        }

        $fasilitas = DB::table('fasilitas_faskes')
            ->join('fasilitas', 'fasilitas.id_fasilitas', '=', 'fasilitas_faskes.id_fasilitas')
            ->where('fasilitas_faskes.id_faskes', $id)
            ->select('fasilitas.id_fasilitas', 'fasilitas.nama_fasilitas')
            ->orderBy('fasilitas.nama_fasilitas', 'asc')
            ->get();

        $spesialisasi = DB::table('spesialisasi_faskes')
            ->join('spesialisasi', 'spesialisasi.id_spesialisasi', '=', 'spesialisasi_faskes.id_spesialisasi')
            ->where('spesialisasi_faskes.id_faskes', $id)
            ->select('spesialisasi.id_spesialisasi', 'spesialisasi.nama_spesialisasi')
            ->orderBy('spesialisasi.nama_spesialisasi', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil mengambil daftar fasilitas dan spesialisasi faskes',
            'data' => [
                'id_faskes' => (int) $id,
                'nama_faskes' => $faskes->nama_faskes,
                'total_fasilitas' => $fasilitas->count(),
                'total_spesialisasi' => $spesialisasi->count(),
                'fasilitas' => $fasilitas,
                'spesialisasi' => $spesialisasi,
            ],
        ]);
    }

    /**
     * Ambil hanya daftar fasilitas yang dimiliki oleh suatu faskes.
     *
     * @param int|string $id
     * @return JsonResponse
     */
    public function getFacilities($id): JsonResponse
    {
        $faskesExists = DB::table('faskes')->where('id_faskes', $id)->exists();

        if (! $faskesExists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Fasilitas kesehatan tidak ditemukan',
            ], 404);
        }

        $fasilitas = DB::table('fasilitas_faskes')
            ->join('fasilitas', 'fasilitas.id_fasilitas', '=', 'fasilitas_faskes.id_fasilitas')
            ->where('fasilitas_faskes.id_faskes', $id)
            ->select('fasilitas.id_fasilitas', 'fasilitas.nama_fasilitas')
            ->orderBy('fasilitas.nama_fasilitas', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil mengambil daftar fasilitas faskes',
            'data' => [
                'id_faskes' => (int) $id,
                'total' => $fasilitas->count(),
                'fasilitas' => $fasilitas,
            ],
        ]);
    }

    /**
     * Ambil hanya daftar spesialisasi dokter yang dimiliki oleh suatu faskes.
     *
     * @param int|string $id
     * @return JsonResponse
     */
    public function getSpecialties($id): JsonResponse
    {
        $faskesExists = DB::table('faskes')->where('id_faskes', $id)->exists();

        if (! $faskesExists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Fasilitas kesehatan tidak ditemukan',
            ], 404);
        }

        $spesialisasi = DB::table('spesialisasi_faskes')
            ->join('spesialisasi', 'spesialisasi.id_spesialisasi', '=', 'spesialisasi_faskes.id_spesialisasi')
            ->where('spesialisasi_faskes.id_faskes', $id)
            ->select('spesialisasi.id_spesialisasi', 'spesialisasi.nama_spesialisasi')
            ->orderBy('spesialisasi.nama_spesialisasi', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil mengambil daftar spesialisasi faskes',
            'data' => [
                'id_faskes' => (int) $id,
                'total' => $spesialisasi->count(),
                'spesialisasi' => $spesialisasi,
            ],
        ]);
    }

    /**
     * Ambil seluruh master daftar fasilitas (bisa difilter via ?search=).
     * Digunakan untuk form Create & Edit di Admin Dashboard.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAllFacilities(Request $request): JsonResponse
    {
        $query = DB::table('fasilitas')->select('id_fasilitas', 'nama_fasilitas');

        if ($request->filled('search')) {
            $query->where('nama_fasilitas', 'like', '%' . trim($request->search) . '%');
        }

        $facilities = $query->orderBy('nama_fasilitas', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'total' => $facilities->count(),
            'data' => $facilities,
        ]);
    }

    /**
     * Ambil seluruh master daftar spesialisasi dokter (bisa difilter via ?search=).
     * Digunakan untuk form Create & Edit di Admin Dashboard.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAllSpecialties(Request $request): JsonResponse
    {
        $query = DB::table('spesialisasi')->select('id_spesialisasi', 'nama_spesialisasi');

        if ($request->filled('search')) {
            $query->where('nama_spesialisasi', 'like', '%' . trim($request->search) . '%');
        }

        $specialties = $query->orderBy('nama_spesialisasi', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'total' => $specialties->count(),
            'data' => $specialties,
        ]);
    }

    /**
     * Tambah master jenis fasilitas baru ke sistem.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function storeFacility(Request $request): JsonResponse
    {
        $request->validate([
            'nama_fasilitas' => 'required|string|max:150',
        ], [
            'nama_fasilitas.required' => 'Nama fasilitas wajib diisi.',
            'nama_fasilitas.max' => 'Nama fasilitas maksimal 150 karakter.',
        ]);

        $nama = trim($request->input('nama_fasilitas'));

        // Cek apakah fasilitas sudah ada (case-insensitive)
        $existing = DB::table('fasilitas')
            ->whereRaw('LOWER(TRIM(nama_fasilitas)) = ?', [mb_strtolower($nama, 'UTF-8')])
            ->first();

        if ($existing) {
            return response()->json([
                'status' => 'success',
                'message' => 'Fasilitas dengan nama tersebut sudah terdaftar di sistem',
                'is_new' => false,
                'data' => $existing,
            ], 200);
        }

        $id = DB::table('fasilitas')->insertGetId([
            'nama_fasilitas' => $nama,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $newFacility = DB::table('fasilitas')->where('id_fasilitas', $id)->first();

        return response()->json([
            'status' => 'success',
            'message' => 'Jenis fasilitas baru berhasil ditambahkan',
            'is_new' => true,
            'data' => $newFacility,
        ], 201);
    }

    /**
     * Alias method untuk storeFacility.
     */
    public function storeFasilitas(Request $request): JsonResponse
    {
        return $this->storeFacility($request);
    }

    /**
     * Tambah master jenis spesialisasi baru ke sistem.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function storeSpecialty(Request $request): JsonResponse
    {
        $request->validate([
            'nama_spesialisasi' => 'required|string|max:100',
        ], [
            'nama_spesialisasi.required' => 'Nama spesialisasi wajib diisi.',
            'nama_spesialisasi.max' => 'Nama spesialisasi dokter maksimal 100 karakter.',
        ]);

        $nama = trim($request->input('nama_spesialisasi'));

        // Cek apakah spesialisasi sudah ada (case-insensitive)
        $existing = DB::table('spesialisasi')
            ->whereRaw('LOWER(TRIM(nama_spesialisasi)) = ?', [mb_strtolower($nama, 'UTF-8')])
            ->first();

        if ($existing) {
            return response()->json([
                'status' => 'success',
                'message' => 'Spesialisasi dengan nama tersebut sudah terdaftar di sistem',
                'is_new' => false,
                'data' => $existing,
            ], 200);
        }

        $id = DB::table('spesialisasi')->insertGetId([
            'nama_spesialisasi' => $nama,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $newSpecialty = DB::table('spesialisasi')->where('id_spesialisasi', $id)->first();

        return response()->json([
            'status' => 'success',
            'message' => 'Jenis spesialisasi baru berhasil ditambahkan',
            'is_new' => true,
            'data' => $newSpecialty,
        ], 201);
    }

    /**
     * Alias method untuk storeSpecialty.
     */
    public function storeSpesialisasi(Request $request): JsonResponse
    {
        return $this->storeSpecialty($request);
    }
}

