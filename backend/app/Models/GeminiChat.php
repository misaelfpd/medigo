<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeminiChat extends Model
{
    use HasFactory;

    protected $table = 'gemini_chats';

    // Boleh ngisi semua kolom mass-assignment
    protected $guarded = [];
}