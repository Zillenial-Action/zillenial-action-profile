<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Midtrans Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk integrasi Midtrans Payment Gateway.
    | Set MIDTRANS_IS_PRODUCTION=false untuk sandbox/testing.
    |
    */

    'server_key'    => env('MIDTRANS_SERVER_KEY'),
    'client_key'    => env('MIDTRANS_CLIENT_KEY'),
    'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
    'is_sanitized'  => env('MIDTRANS_IS_SANITIZED', true),
    'is_3ds'        => env('MIDTRANS_IS_3DS', true),

    /*
    | Batas waktu pembayaran (menit) — dipakai custom_expiry Midtrans, timer frontend, & release stok.
    | Grace: jeda setelah expiry sebelum stok dilepas. TTL: fallback bila expiry_time tak tersedia.
    */
    'payment_expiry_minutes' => (int) env('MIDTRANS_PAYMENT_EXPIRY_MINUTES', 15),
    'expiry_grace_minutes'   => (int) env('MIDTRANS_EXPIRY_GRACE_MINUTES', 15),
    'pending_ttl_hours'      => (int) env('MIDTRANS_PENDING_TTL_HOURS', 25),

    /*
    | URL Snap Midtrans (otomatis dipilih berdasarkan is_production)
    */
    'snap_url' => env('MIDTRANS_IS_PRODUCTION', false)
        ? 'https://app.midtrans.com/snap/snap.js'
        : 'https://app.sandbox.midtrans.com/snap/snap.js',

    'frontend_url' => env('FRONTEND_URL', ''),

];