<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faskes extends Model
{
    protected $table = 'faskes';
    protected $primaryKey = 'id_faskes';
    public $timestamps = false;

    public function spesialisasi()
    {
        return $this->belongsToMany(Spesialisasi::class, 'spesialisasi_faskes', 'id_faskes', 'id_spesialisasi');
    }

    public function fasilitas()
    {
        return $this->belongsToMany(Fasilitas::class, 'fasilitas_faskes', 'id_faskes', 'id_fasilitas');
    }
}