<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FotoFaskes extends Model
{
    public const TIPE_THUMBNAIL = 'thumbnail';

    public const TIPE_BANNER = 'banner';

    public const TIPE_GALERI = 'galeri';

    public const TIPE = [self::TIPE_THUMBNAIL, self::TIPE_BANNER, self::TIPE_GALERI];

    /** Batas jumlah foto galeri per faskes. */
    public const MAKS_GALERI = 20;

    protected $table = 'foto_faskes';

    protected $primaryKey = 'id_foto';

    protected $guarded = [];

    protected $casts = [
        'urutan' => 'integer',
        'lebar' => 'integer',
        'tinggi' => 'integer',
        'ukuran_bytes' => 'integer',
    ];

    public function faskes()
    {
        return $this->belongsTo(Faskes::class, 'id_faskes', 'id_faskes');
    }

    /**
     * URL publik file foto. Pakai url('storage/...') seperti gambar diskusi yang sudah ada.
     */
    public static function urlFromPath(?string $path): ?string
    {
        return $path ? url('storage/'.ltrim($path, '/')) : null;
    }

    /**
     * Bentuk JSON yang dikirim ke frontend.
     */
    public function toApi(): array
    {
        return [
            'id_foto' => $this->id_foto,
            'id_faskes' => $this->id_faskes,
            'tipe' => $this->tipe,
            'url' => self::urlFromPath($this->path_foto),
            'keterangan' => $this->keterangan,
            'urutan' => $this->urutan,
            'lebar' => $this->lebar,
            'tinggi' => $this->tinggi,
            'ukuran_bytes' => $this->ukuran_bytes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
