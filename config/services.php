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
    'kepegawaian' => [
        'url' => env('URL_APP_KARYAWAN') ?: env('PEGAWAI_API_URL'),
        'app_url' => env('URL_APP_KARYAWAN'),
        'api_key' => env('PEGAWAI_API_KEY'),
    ],

    'fingerspot' => [
        'base_url' => rtrim((string) env('FINGERSPOT_BASE_URL', 'https://developer.fingerspot.io/api'), '/'),
        'endpoint_path' => '/'.ltrim((string) env('FINGERSPOT_ENDPOINT_PATH', 'get_attlog'), '/'),
        'api_token' => env('FINGERSPOT_API_TOKEN'),
        'cloud_ids' => array_values(array_unique(array_filter(
            array_map('trim', explode(',', (string) env('FINGERSPOT_CLOUD_IDS', ''))),
            'strlen'
        ))),
        'timeout' => (int) env('FINGERSPOT_TIMEOUT', 30),
        'retry_times' => (int) env('FINGERSPOT_RETRY_TIMES', 3),
        'retry_sleep_ms' => (int) env('FINGERSPOT_RETRY_SLEEP_MS', 1000),
    ],

];
