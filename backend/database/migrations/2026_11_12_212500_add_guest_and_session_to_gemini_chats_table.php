<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gemini_chats', function (Blueprint $table) {
            // Tamu (belum login) tidak punya id_user, jadi kolom ini dibuat nullable
            $table->unsignedBigInteger('id_user')->nullable()->change();

            // ID unik tamu yang dibuat dari session frontend
            $table->string('guest_id', 64)->nullable()->after('id_user')->index();

            // Waktu mulai sesi chat. Memori & jatah prompt berlaku 24 jam sejak waktu ini
            $table->timestamp('sesi_mulai')->nullable()->after('guest_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('gemini_chats', function (Blueprint $table) {
            $table->dropIndex(['guest_id']);
            $table->dropIndex(['sesi_mulai']);
            $table->dropColumn(['guest_id', 'sesi_mulai']);
        });
    }
};

