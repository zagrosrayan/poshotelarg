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

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'payamak' => [
        'username' => env('API_USERNAME_MELI_PAYAMAK'),
        'password' => env('API_KEY_MELI_PAYAMAK'),
        'from' => env('API_FROM_MELI_PAYAMAK'),
        'body_ids' => [
            'next_purchase_issued' => (int) env('PAYAMAK_BODY_ID_NEXT_PURCHASE', 499216),
            'next_purchase_reminder' => (int) env('PAYAMAK_BODY_ID_NEXT_PURCHASE_REMINDER', 499219),
        ],
        'reminder_days_before_expiration' => 4,
        'send_window_start_hour' => 10,
        'send_window_end_hour' => 21,
        'max_attempts' => 3,
    ],

];
