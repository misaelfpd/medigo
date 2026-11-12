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

    protected $casts = [
        'sesi_mulai' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (GeminiChat $chat) {
            if ($chat->sesi_mulai === null) {
                $baseTime = $chat->created_at ? \Illuminate\Support\Carbon::parse($chat->created_at) : now();
                $cutoff = $baseTime->copy()->subHours(24);

                $prev = static::query()
                    ->when($chat->id_user, fn ($q) => $q->where('id_user', $chat->id_user))
                    ->when(! $chat->id_user && $chat->guest_id, fn ($q) => $q->whereNull('id_user')->where('guest_id', $chat->guest_id))
                    ->whereNotNull('sesi_mulai')
                    ->where('sesi_mulai', '>', $cutoff)
                    ->orderByDesc('id')
                    ->first();

                $chat->sesi_mulai = $prev ? $prev->sesi_mulai : $baseTime->copy()->startOfSecond();
            }
        });
    }
}