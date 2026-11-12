<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fasilitas extends Model
{
    use HasFactory;

    protected $table = 'fasilitas';
    protected $primaryKey = 'id_fasilitas';
    protected $guarded = [];

    public function faskes()
    {
        return $this->belongsToMany(Faskes::class, 'fasilitas_faskes', 'id_fasilitas', 'id_faskes');
    }
}
