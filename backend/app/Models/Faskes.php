<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faskes extends Model
{
    use HasFactory;

    protected $table = 'faskes';

    protected $primaryKey = 'id_faskes';

    protected $guarded = [];

    public function fasilitas()
    {
        return $this->belongsToMany(Fasilitas::class, 'fasilitas_faskes', 'id_faskes', 'id_fasilitas');
    }

    public function foto()
    {
        return $this->hasMany(FotoFaskes::class, 'id_faskes', 'id_faskes');
    }

    public function spesialisasi()
    {
        return $this->belongsToMany(Spesialisasi::class, 'spesialisasi_faskes', 'id_faskes', 'id_spesialisasi');
    }
}
