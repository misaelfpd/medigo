<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Shared secret the frontend server sends as X-Service-Key to
    // POST /api/auth/token-by-email. Leave empty to disable that route.
    'frontend' => [
        'service_key' => env('MEDIGO_SERVICE_KEY'),
    ],

    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        // Tried in order; the next one is used when a model is overloaded, rate limited or times out.
        'models' => array_values(array_filter(array_map('trim', explode(',', (string) env('GEMINI_MODELS', 'gemini-3.1-flash-lite,gemini-3.5-flash-lite'))))),
        'timeout' => (int) env('GEMINI_TIMEOUT', 8),
        // Batas pesan per menit (per akun untuk user login, per IP untuk tamu)
        'per_minute_limit' => (int) env('MEDIBOT_PER_MINUTE_LIMIT', 30),
        // Batas prompt tamu per IP asli dalam 24 jam (di luar jatah 6 prompt per sesi tamu)
        'guest_ip_daily_limit' => (int) env('MEDIBOT_GUEST_IP_DAILY_LIMIT', 20),
        // true = tamu hanya boleh chat lewat server frontend (wajib header X-Service-Key).
        // Aktifkan setelah frontend mengirim X-Service-Key + X-Client-IP ke /api/chat-ai.
        'guest_require_frontend' => filter_var(env('MEDIBOT_GUEST_REQUIRE_FRONTEND', false), FILTER_VALIDATE_BOOLEAN),
    ],

    // Jarak rute jalan (OSRM) untuk daftar faskes terdekat
    'osrm' => [
        'enabled' => filter_var(env('OSRM_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
        'url' => env('OSRM_TABLE_URL', 'https://router.project-osrm.org/table/v1/driving/'),
        // Maksimal faskes yang jaraknya dihitung lewat OSRM per request (sisanya pakai jarak garis lurus)
        'max_items' => (int) env('OSRM_MAX_ITEMS', 20),
        // Batas waktu total (detik) untuk semua panggilan OSRM dalam satu request
        'timeout' => (float) env('OSRM_TIMEOUT', 2.5),
    ],

];
