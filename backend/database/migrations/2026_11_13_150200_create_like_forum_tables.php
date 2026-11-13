<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Like untuk diskusi dan komentar (tanggapan) forum.
     * Unique index menjamin satu user hanya bisa like satu kali per diskusi/komentar,
     * termasuk kalau tombol like ditekan dua kali bersamaan.
     */
    public function up(): void
    {
        Schema::create('like_diskusi', function (Blueprint $table) {
            $table->id('id_like_diskusi');
            $table->unsignedBigInteger('id_diskusi');
            $table->unsignedBigInteger('id_user');
            $table->timestamps();

            $table->foreign('id_diskusi')->references('id_diskusi')->on('diskusi')->onDelete('cascade');
            $table->foreign('id_user')->references('id_user')->on('user')->onDelete('cascade');
            $table->unique(['id_diskusi', 'id_user']);
        });

        Schema::create('like_tanggapan', function (Blueprint $table) {
            $table->id('id_like_tanggapan');
            $table->unsignedBigInteger('id_tanggapan');
            $table->unsignedBigInteger('id_user');
            $table->timestamps();

            $table->foreign('id_tanggapan')->references('id_tanggapan')->on('tanggapan_diskusi')->onDelete('cascade');
            $table->foreign('id_user')->references('id_user')->on('user')->onDelete('cascade');
            $table->unique(['id_tanggapan', 'id_user']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('like_tanggapan');
        Schema::dropIfExists('like_diskusi');
    }
};
