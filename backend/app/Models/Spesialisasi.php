<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Spesialisasi extends Model
{
    protected $table = 'spesialisasi';
    protected $primaryKey = 'id_spesialisasi';
    protected $guarded = [];
    public $timestamps = true;

    public function faskes()
    {
        return $this->belongsToMany(Faskes::class, 'spesialisasi_faskes', 'id_spesialisasi', 'id_faskes');
    }
}