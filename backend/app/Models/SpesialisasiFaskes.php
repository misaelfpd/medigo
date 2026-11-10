<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpesialisasiFaskes extends Model
{
    protected $table = 'spesialisasi_faskes';
    protected $primaryKey = 'id_spesialisasi_faskes'; // Sesuaikan jika ada id-nya di db
    public $timestamps = false;
}