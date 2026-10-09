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

    /*
    | Google Sign-In (semua role). Backend SELALU memverifikasi ID token Google
    | (signature, issuer, audience/client ID, expiry, email_verified).
    */
    'google' => [
        // OAuth client "Web application" (dipakai dashboard Filament).
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', rtrim((string) env('APP_URL', 'http://localhost'), '/').'/auth/google/callback'),
        // Audience yang diterima untuk ID token dari aplikasi Android.
        // Android (Credential Manager) memakai serverClientId = GOOGLE_CLIENT_ID,
        // sehingga aud token = web client ID. Client ID tambahan dipisah koma.
        'allowed_audiences' => array_values(array_filter(array_map('trim', explode(',', (string) env('GOOGLE_CLIENT_ID').','.(string) env('GOOGLE_EXTRA_CLIENT_IDS', ''))))),
        'certs_url' => 'https://www.googleapis.com/oauth2/v1/certs',
        'auth_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
        'token_url' => 'https://oauth2.googleapis.com/token',
    ],

];
