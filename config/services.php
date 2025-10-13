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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | OCR Service Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for external OCR API service to process bank statements
    |
    */

 /*
    |--------------------------------------------------------------------------
    | OCR Service Configuration
    |--------------------------------------------------------------------------
    */

    'ocr' => [
        'url' => env('OCR_API_URL', 'http://38.60.179.13:40040/api/upload-pdf/bank-statement/monthly'),
        'key' => env('OCR_API_KEY', ''),
        'timeout' => env('OCR_API_TIMEOUT', 180), // 3 minutes
        'mock_mode' => env('OCR_MOCK_MODE', false), // Enable for testing
        'retry_times' => env('OCR_RETRY_TIMES', 3),
        'retry_delay' => env('OCR_RETRY_DELAY', 2), // seconds
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', env('APP_URL') . '/auth/google/callback'),
    ],
];