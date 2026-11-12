<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gemini_chats', function (Blueprint $table) {
            $table->json('faskes')->nullable()->after('message');
        });
    }

    public function down(): void
    {
        Schema::table('gemini_chats', function (Blueprint $table) {
            $table->dropColumn('faskes');
        });
    }
};

