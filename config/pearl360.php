<?php

/*
|--------------------------------------------------------------------------
| Pearl360 – Uganda Parks Virtual Tour & Smart Trip Planner
|--------------------------------------------------------------------------
| Values here are defaults. Most can be overridden in .env, and contact
| details / exchange rate can also be changed by the admin under Settings.
*/

return [

    'name' => env('APP_NAME', 'Pearl360 Uganda'),

    // Default admin contact – receives booking & payment alerts (email + SMS).
    'admin_email' => env('ADMIN_EMAIL', '2400725284@mubs.ac.ug'),
    'admin_phone' => env('ADMIN_PHONE', '256741141433'),

    // Indicative USD → UGX rate used to show park costs in shillings.
    'usd_rate' => env('USD_UGX_RATE', 3700),

    // Folder under public/ where all uploaded photos, videos and 360° images are stored.
    'upload_dir' => 'uploads',

    'payments' => [
        // "simulate" works offline for demos/defence. Switch to "flutterwave" for real
        // MTN MoMo, Airtel Money and card payments in Uganda.
        'driver' => env('PAYMENT_DRIVER', 'simulate'),
        'currency' => 'UGX',

        'flutterwave' => [
            'public_key' => env('FLUTTERWAVE_PUBLIC_KEY'),
            'secret_key' => env('FLUTTERWAVE_SECRET_KEY'),
            'secret_hash' => env('FLUTTERWAVE_SECRET_HASH'), // webhook verification
            'base_url' => env('FLUTTERWAVE_BASE_URL', 'https://api.flutterwave.com/v3'),
        ],
    ],

    'sms' => [
        // "log" stores messages in the sms_logs table only (no cost). "africastalking" sends real SMS.
        'driver' => env('SMS_DRIVER', 'log'),

        'africastalking' => [
            'username' => env('AT_USERNAME', 'sandbox'),
            'api_key' => env('AT_API_KEY'),
            'from' => env('AT_SENDER_ID'), // optional alphanumeric sender id
            'sandbox' => env('AT_SANDBOX', true),
        ],
    ],
];
