<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaporanTanggapan extends Model
{
    use HasFactory;

    protected $table = 'laporan_tanggapan';
    protected $primaryKey = 'id_laporan_tanggapan';
    protected $guarded = [];

    public function tanggapan()
    {
        return $this->belongsTo(TanggapanDiskusi::class, 'id_tanggapan', 'id_tanggapan');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }
}

