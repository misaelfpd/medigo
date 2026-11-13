<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Faskes yang di-mention di judul diskusi (maksimal 1 faskes per diskusi).
     * Kalau faskesnya dihapus, diskusinya tetap ada dan mention-nya jadi null.
     */
    public function up(): void
    {
        Schema::table('diskusi', function (Blueprint $table) {
            if (! Schema::hasColumn('diskusi', 'id_faskes_mention')) {
                $table->unsignedBigInteger('id_faskes_mention')->nullable()->after('kategori');
                $table->foreign('id_faskes_mention')->references('id_faskes')->on('faskes')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('diskusi', function (Blueprint $table) {
            if (Schema::hasColumn('diskusi', 'id_faskes_mention')) {
                $table->dropForeign(['id_faskes_mention']);
                $table->dropColumn('id_faskes_mention');
            }
        });
    }
};
