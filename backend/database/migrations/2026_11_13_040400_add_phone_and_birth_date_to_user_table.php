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
        Schema::table('user', function (Blueprint $table) {
            if (!Schema::hasColumn('user', 'no_telp')) {
                $table->string('no_telp', 50)->nullable()->after('email_user');
            }
            if (!Schema::hasColumn('user', 'tanggal_lahir')) {
                $table->date('tanggal_lahir')->nullable()->after('no_telp');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user', function (Blueprint $table) {
            $table->dropColumn(['no_telp', 'tanggal_lahir']);
        });
    }
};

