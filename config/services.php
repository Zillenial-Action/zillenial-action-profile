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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'api_key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'chatkebaikan' => [
        'base_url' => env('CHATKEBAIKAN_BASE_URL', 'https://chatkebaikan.raihmimpi.id'),
        'validate_path' => env('CHATKEBAIKAN_VALIDATE_PATH', '/api/dr/voucher/validate/{kode}'),
        'redeem_url' => env('CHATKEBAIKAN_REDEEM_URL', 'https://chatkebaikan.raihmimpi.id/api/dr/voucher/redeem'),
        'redeem_method' => env('CHATKEBAIKAN_REDEEM_METHOD', 'POST'),
        'timeout' => (int) env('CHATKEBAIKAN_TIMEOUT', 10),
    ],

];
