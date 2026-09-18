<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
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

    // API key untuk ingest sensor dari ESP32 (POST /api/sensor/store).
    // Dibaca via config() agar tetap valid saat config:cache aktif (env() jadi null saat runtime).
    'sensor' => [
        'api_key'     => env('SENSOR_API_KEY'),
        'temp_limit'  => env('SENSOR_TEMP_LIMIT', 35),
        'smoke_limit' => env('SENSOR_SMOKE_LIMIT', 600),
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_id'   => env('TELEGRAM_CHAT_ID'),
    ],

    // Web Push VAPID keys — generate: `web-push generate-vapid-keys`
    'push' => [
        'vapid_public'  => env('VAPID_PUBLIC_KEY'),
        'vapid_private' => env('VAPID_PRIVATE_KEY'),
        'vapid_subject' => env('VAPID_SUBJECT', 'mailto:it.inkasajayaaluminium@gmail.com'),
    ],

];
