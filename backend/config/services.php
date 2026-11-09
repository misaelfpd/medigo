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
        'models' => array_values(array_filter(array_map('trim', explode(',', (string) env('GEMINI_MODELS', 'gemini-3.5-flash-lite,gemini-3.8-flash'))))),
        'timeout' => (int) env('GEMINI_TIMEOUT', 30),
    ],

];
