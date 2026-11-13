<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Foto faskes. Satu faskes punya:
     * - maksimal 1 'thumbnail' (kartu/list di beranda),
     * - maksimal 1 'banner' (foto panjang di atas halaman detail),
     * - beberapa 'galeri' (ditampilkan di bawah banner).
     * Semua file disimpan dalam format WebP di disk public (storage/app/public/faskes/{id}).
     */
    public function up(): void
    {
        Schema::create('foto_faskes', function (Blueprint $table) {
            $table->id('id_foto');
            $table->unsignedBigInteger('id_faskes');
            $table->string('tipe', 20); // thumbnail | banner | galeri
            $table->string('path_foto'); // path relatif di disk public, contoh: faskes/12/galeri-xxxx.webp
            $table->string('keterangan', 255)->nullable();
            $table->unsignedInteger('urutan')->default(0);
            $table->unsignedInteger('lebar')->nullable();
            $table->unsignedInteger('tinggi')->nullable();
            $table->unsignedInteger('ukuran_bytes')->nullable();
            $table->timestamps();

            $table->foreign('id_faskes')->references('id_faskes')->on('faskes')->onDelete('cascade');
            $table->index(['id_faskes', 'tipe']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('foto_faskes');
    }
};
