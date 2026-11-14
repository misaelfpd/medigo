<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catatan tambahan (opsional) dari pelapor untuk laporan diskusi dan laporan komentar diskusi,
 * menyamakan dengan laporan_komentar (ulasan faskes) yang sudah punya kolom ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['laporan_diskusi', 'laporan_tanggapan'] as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'catatan_tambahan')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->text('catatan_tambahan')->nullable()->after('alasan_laporan');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['laporan_diskusi', 'laporan_tanggapan'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'catatan_tambahan')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropColumn('catatan_tambahan');
                });
            }
        }
    }
};
