<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('laporan_diskusi')) {
            Schema::table('laporan_diskusi', function (Blueprint $table) {
                if (! Schema::hasColumn('laporan_diskusi', 'status')) {
                    $table->string('status', 50)->default('pending')->after('alasan_laporan');
                }
                if (! Schema::hasColumn('laporan_diskusi', 'created_at')) {
                    $table->timestamps();
                }
            });
        }

        if (Schema::hasTable('laporan_tanggapan')) {
            Schema::table('laporan_tanggapan', function (Blueprint $table) {
                if (! Schema::hasColumn('laporan_tanggapan', 'status')) {
                    $table->string('status', 50)->default('pending')->after('alasan_laporan');
                }
                if (! Schema::hasColumn('laporan_tanggapan', 'created_at')) {
                    $table->timestamps();
                }
            });
        }

        if (Schema::hasTable('tanggapan_diskusi')) {
            Schema::table('tanggapan_diskusi', function (Blueprint $table) {
                if (! Schema::hasColumn('tanggapan_diskusi', 'created_at')) {
                    $table->timestamps();
                }
            });
        }

        if (Schema::hasTable('faskes')) {
            Schema::table('faskes', function (Blueprint $table) {
                if (Schema::hasColumn('faskes', '_support_bpjs')) {
                    $table->boolean('_support_bpjs')->nullable()->default(false)->change();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('laporan_diskusi')) {
            Schema::table('laporan_diskusi', function (Blueprint $table) {
                if (Schema::hasColumn('laporan_diskusi', 'status')) {
                    $table->dropColumn('status');
                }
                if (Schema::hasColumn('laporan_diskusi', 'created_at')) {
                    $table->dropTimestamps();
                }
            });
        }

        if (Schema::hasTable('laporan_tanggapan')) {
            Schema::table('laporan_tanggapan', function (Blueprint $table) {
                if (Schema::hasColumn('laporan_tanggapan', 'status')) {
                    $table->dropColumn('status');
                }
                if (Schema::hasColumn('laporan_tanggapan', 'created_at')) {
                    $table->dropTimestamps();
                }
            });
        }

        if (Schema::hasTable('tanggapan_diskusi')) {
            Schema::table('tanggapan_diskusi', function (Blueprint $table) {
                if (Schema::hasColumn('tanggapan_diskusi', 'created_at')) {
                    $table->dropTimestamps();
                }
            });
        }
    }
};
