<?php

namespace App\Http\Controllers;

use App\Models\FotoFaskes;
use App\Services\WebpImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * CRUD foto faskes (thumbnail, banner, galeri). Semua upload dikonversi ke WebP.
 */
class FotoFaskesController extends Controller
{
    /** Format yang boleh di-upload (akan dikonversi ke WebP). */
    private const MIMES = 'jpg,jpeg,png,webp,gif,bmp';

    /** Batas ukuran file upload dalam KB (sama seperti gambar diskusi). */
    private const MAX_KB = 5120;

    /** Batas file per sekali upload galeri. */
    private const MAX_FILES_PER_UPLOAD = 10;

    public function __construct(private WebpImageService $images) {}

    /**
     * Public: semua foto satu faskes, dikelompokkan per tipe.
     */
    public function index($id): JsonResponse
    {
        if (! DB::table('faskes')->where('id_faskes', $id)->exists()) {
            return $this->faskesNotFound();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil mengambil foto faskes',
            'data' => self::photosFor((int) $id),
        ]);
    }

    /**
     * Admin: upload foto baru.
     * - tipe=thumbnail / banner: 1 file, menggantikan foto lama dengan tipe yang sama.
     * - tipe=galeri: boleh beberapa file sekaligus (foto[]).
     */
    public function store(Request $request, $id): JsonResponse
    {
        if (! DB::table('faskes')->where('id_faskes', $id)->exists()) {
            return $this->faskesNotFound();
        }

        $request->validate([
            'tipe' => 'required|in:'.implode(',', FotoFaskes::TIPE),
            'keterangan' => 'nullable|string|max:255',
            'urutan' => 'nullable|integer|min:0',
        ], [
            'tipe.required' => 'Tipe foto wajib diisi (thumbnail, banner, atau galeri).',
            'tipe.in' => 'Tipe foto harus thumbnail, banner, atau galeri.',
        ]);

        $tipe = $request->input('tipe');
        $files = $this->validatedFiles($request, $tipe === FotoFaskes::TIPE_GALERI);

        if ($tipe === FotoFaskes::TIPE_GALERI) {
            $existing = FotoFaskes::where('id_faskes', $id)->where('tipe', FotoFaskes::TIPE_GALERI)->count();
            if ($existing + count($files) > FotoFaskes::MAKS_GALERI) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Galeri maksimal '.FotoFaskes::MAKS_GALERI.' foto. Saat ini sudah ada '.$existing.' foto.',
                ], 422);
            }
        }

        if (! WebpImageService::isSupported()) {
            return $this->webpUnsupported();
        }

        $saved = [];
        try {
            $nextOrder = (int) FotoFaskes::where('id_faskes', $id)->where('tipe', $tipe)->max('urutan');
            foreach ($files as $i => $file) {
                $stored = $this->images->storeUpload($file, "faskes/{$id}", $tipe);
                $saved[] = $this->saveRow((int) $id, $tipe, $stored, [
                    'keterangan' => $request->input('keterangan'),
                    'urutan' => $request->filled('urutan') ? (int) $request->input('urutan') + $i : $nextOrder + $i + 1,
                ]);
            }
        } catch (RuntimeException $e) {
            // Rollback file yang terlanjur tersimpan di upload yang sama
            foreach ($saved as $foto) {
                $this->images->delete($foto->path_foto);
                $foto->delete();
            }

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => count($saved) > 1 ? count($saved).' foto berhasil diunggah' : 'Foto berhasil diunggah',
            'data' => collect($saved)->map->toApi()->values(),
        ], 201);
    }

    /**
     * Admin: ubah keterangan/urutan, dan/atau ganti file foto.
     * Kirim lewat POST (multipart) kalau mengganti file, karena PHP tidak membaca file di PUT.
     */
    public function update(Request $request, $id, $fotoId): JsonResponse
    {
        $foto = FotoFaskes::where('id_faskes', $id)->where('id_foto', $fotoId)->first();
        if (! $foto) {
            return $this->photoNotFound();
        }

        $request->validate([
            'keterangan' => 'nullable|string|max:255',
            'urutan' => 'nullable|integer|min:0',
            'foto' => 'nullable|file|mimes:'.self::MIMES.'|max:'.self::MAX_KB,
        ], $this->fileMessages());

        if ($request->has('keterangan')) {
            $foto->keterangan = $request->input('keterangan');
        }
        if ($request->filled('urutan')) {
            $foto->urutan = (int) $request->input('urutan');
        }

        if ($request->hasFile('foto')) {
            if (! WebpImageService::isSupported()) {
                return $this->webpUnsupported();
            }

            try {
                $stored = $this->images->storeUpload($request->file('foto'), "faskes/{$id}", $foto->tipe);
            } catch (RuntimeException $e) {
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
            }

            $oldPath = $foto->path_foto;
            $foto->fill([
                'path_foto' => $stored['path'],
                'lebar' => $stored['lebar'],
                'tinggi' => $stored['tinggi'],
                'ukuran_bytes' => $stored['ukuran_bytes'],
            ]);
            $foto->save();
            $this->images->delete($oldPath);
        } else {
            $foto->save();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Foto berhasil diperbarui',
            'data' => $foto->toApi(),
        ]);
    }

    /**
     * Admin: hapus satu foto (file WebP ikut dihapus).
     */
    public function destroy($id, $fotoId): JsonResponse
    {
        $foto = FotoFaskes::where('id_faskes', $id)->where('id_foto', $fotoId)->first();
        if (! $foto) {
            return $this->photoNotFound();
        }

        $this->images->delete($foto->path_foto);
        $foto->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Foto berhasil dihapus',
        ]);
    }

    /**
     * Admin: jadikan foto yang sudah ada (biasanya foto galeri) sebagai thumbnail atau banner.
     * Foto aslinya tetap di galeri; dibuat salinan WebP baru dengan ukuran sesuai tipe tujuan.
     */
    public function setAs(Request $request, $id, $fotoId): JsonResponse
    {
        $source = FotoFaskes::where('id_faskes', $id)->where('id_foto', $fotoId)->first();
        if (! $source) {
            return $this->photoNotFound();
        }

        $request->validate([
            'tipe' => 'required|in:'.FotoFaskes::TIPE_THUMBNAIL.','.FotoFaskes::TIPE_BANNER,
        ], [
            'tipe.required' => 'Tipe tujuan wajib diisi (thumbnail atau banner).',
            'tipe.in' => 'Foto hanya bisa dijadikan thumbnail atau banner.',
        ]);

        $tipe = $request->input('tipe');
        if ($source->tipe === $tipe) {
            return response()->json([
                'status' => 'success',
                'message' => 'Foto ini sudah menjadi '.$tipe.'.',
                'data' => $source->toApi(),
            ]);
        }

        if (! WebpImageService::isSupported()) {
            return $this->webpUnsupported();
        }

        try {
            $stored = $this->images->copyFromPublicPath($source->path_foto, "faskes/{$id}", $tipe);
        } catch (RuntimeException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }

        $foto = $this->saveRow((int) $id, $tipe, $stored, [
            'keterangan' => $source->keterangan,
            'urutan' => 0,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Foto berhasil dijadikan '.$tipe,
            'data' => $foto->toApi(),
        ], 201);
    }

    /**
     * Simpan baris foto. Untuk thumbnail/banner, foto lama dengan tipe yang sama dihapus
     * supaya selalu hanya ada satu.
     */
    private function saveRow(int $faskesId, string $tipe, array $stored, array $extra): FotoFaskes
    {
        $old = collect();
        if ($tipe !== FotoFaskes::TIPE_GALERI) {
            $old = FotoFaskes::where('id_faskes', $faskesId)->where('tipe', $tipe)->get();
        }

        try {
            $foto = DB::transaction(function () use ($faskesId, $tipe, $stored, $extra, $old) {
                $old->each->delete();

                return FotoFaskes::create([
                    'id_faskes' => $faskesId,
                    'tipe' => $tipe,
                    'path_foto' => $stored['path'],
                    'keterangan' => $extra['keterangan'] ?? null,
                    'urutan' => $extra['urutan'] ?? 0,
                    'lebar' => $stored['lebar'],
                    'tinggi' => $stored['tinggi'],
                    'ukuran_bytes' => $stored['ukuran_bytes'],
                ]);
            });
        } catch (\Throwable $e) {
            $this->images->delete($stored['path']);
            throw $e;
        }

        // File lama baru dihapus setelah data baru tersimpan
        $old->each(fn (FotoFaskes $f) => $this->images->delete($f->path_foto));

        return $foto;
    }

    /**
     * Ambil file dari field 'foto' (boleh 1 file atau array file untuk galeri) lalu validasi.
     *
     * @return UploadedFile[]
     */
    private function validatedFiles(Request $request, bool $allowMultiple): array
    {
        $input = $request->file('foto');

        if (is_array($input) && $allowMultiple) {
            $request->validate([
                'foto' => 'required|array|min:1|max:'.self::MAX_FILES_PER_UPLOAD,
                'foto.*' => 'file|mimes:'.self::MIMES.'|max:'.self::MAX_KB,
            ], $this->fileMessages() + [
                'foto.max' => 'Maksimal '.self::MAX_FILES_PER_UPLOAD.' foto per sekali upload.',
            ]);

            return array_values($input);
        }

        $request->validate([
            'foto' => 'required|file|mimes:'.self::MIMES.'|max:'.self::MAX_KB,
        ], $this->fileMessages() + [
            'foto.file' => $allowMultiple ? 'File foto tidak valid.' : 'Thumbnail dan banner hanya boleh 1 foto.',
        ]);

        return [$input];
    }

    private function fileMessages(): array
    {
        return [
            'foto.required' => 'File foto wajib diunggah.',
            'foto.mimes' => 'Format foto harus JPG, PNG, WebP, GIF, atau BMP.',
            'foto.max' => 'Ukuran foto maksimal 5 MB.',
            'foto.*.mimes' => 'Format foto harus JPG, PNG, WebP, GIF, atau BMP.',
            'foto.*.max' => 'Ukuran setiap foto maksimal 5 MB.',
        ];
    }

    /**
     * Foto satu faskes dalam bentuk { thumbnail, banner, galeri[] }.
     */
    public static function photosFor(int $faskesId): array
    {
        $photos = FotoFaskes::where('id_faskes', $faskesId)
            ->orderBy('urutan')
            ->orderBy('id_foto')
            ->get();

        $thumbnail = $photos->firstWhere('tipe', FotoFaskes::TIPE_THUMBNAIL);
        $banner = $photos->firstWhere('tipe', FotoFaskes::TIPE_BANNER);

        return [
            'thumbnail' => $thumbnail?->toApi(),
            'banner' => $banner?->toApi(),
            'galeri' => $photos->where('tipe', FotoFaskes::TIPE_GALERI)->map->toApi()->values()->all(),
        ];
    }

    /**
     * URL thumbnail untuk banyak faskes sekaligus (dipakai di list beranda/pencarian).
     *
     * @return array<int, string> id_faskes => url
     */
    public static function thumbnailUrls(array $faskesIds): array
    {
        if (empty($faskesIds)) {
            return [];
        }

        $photos = FotoFaskes::whereIn('id_faskes', $faskesIds)
            ->get(['id_faskes', 'tipe', 'path_foto']);

        $result = [];
        foreach ($faskesIds as $id) {
            $faskesPhotos = $photos->where('id_faskes', $id);
            $chosen = $faskesPhotos->firstWhere('tipe', FotoFaskes::TIPE_THUMBNAIL)
                ?? $faskesPhotos->firstWhere('tipe', FotoFaskes::TIPE_BANNER)
                ?? $faskesPhotos->first();

            if ($chosen && $chosen->path_foto) {
                $result[$id] = FotoFaskes::urlFromPath($chosen->path_foto);
            }
        }

        return $result;
    }

    /**
     * Hapus semua file foto milik faskes (dipanggil sebelum faskes dihapus).
     */
    public static function deleteAllFiles(int $faskesId): void
    {
        Storage::disk('public')->deleteDirectory("faskes/{$faskesId}");
    }

    private function faskesNotFound(): JsonResponse
    {
        return response()->json(['status' => 'error', 'message' => 'Fasilitas kesehatan tidak ditemukan'], 404);
    }

    private function photoNotFound(): JsonResponse
    {
        return response()->json(['status' => 'error', 'message' => 'Foto tidak ditemukan'], 404);
    }

    private function webpUnsupported(): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => 'Server belum mendukung konversi WebP. Aktifkan ekstensi GD (extension=gd) di php.ini.',
        ], 500);
    }
}
