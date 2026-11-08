<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ini tabel user custom buatan lu persis
        Schema::create('user', function (Blueprint $table) {
            $table->id('id_user');
            $table->string('nama_user', 150);
            $table->string('email_user', 150)->unique();
            $table->string('password_user');
            $table->string('role_user', 50)->default('user');
        });

        // 2. Biarkan dua tabel bawaan ini biar sistem core Laravel tetep aman
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user'); // Udah disesuaikan jadi 'user'
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};