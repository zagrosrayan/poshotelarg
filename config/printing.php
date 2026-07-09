<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Driver
    |--------------------------------------------------------------------------
    |
    | Supported: `printnode`, `cups`
    |
    */
    'driver' => env('PRINTING_DRIVER', 'cups'),

    /*
    |--------------------------------------------------------------------------
    | Drivers
    |--------------------------------------------------------------------------
    |
    | Configuration for each driver.
    |
    */
    'drivers' => [
        'cups' => [
            'ip' => env('CUPS_SERVER_IP', '192.168.10.40'),
            'username' => env('CUPS_SERVER_USERNAME', 'root'),
            'password' => env('CUPS_SERVER_PASSWORD', '@rghotel2025'),
            'port' => env('CUPS_SERVER_PORT', 631),
        ],


        /*
         * Add your custom drivers here:
         *
         * 'custom' => [
         *      'driver' => 'custom_driver',
         *      // other config for your custom driver
         * ],
         */
//        'custom' => [
//            'driver' => 'raw', // درایور RAW برای اتصال به پرینتر
//            'host' => '192.168.10.199', // آی‌پی پرینتر
//            'port' => 9100, // پورت پرینتر
//            'encoding' => 'UTF-8', // تنظیم انکدینگ
//        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Printer Id
    |--------------------------------------------------------------------------
    |
    | If you know the id of a default printer you want to use, enter it here.
    |
    */
    'default_printer_id' => null,

    /*
    |--------------------------------------------------------------------------
    | Receipt Printer Options
    |--------------------------------------------------------------------------
    |
    */
    'receipts' => [
        /*
         * How many characters fit across a single line on the receipt paper.
         * Adjust according to your needs.
         */
        'line_character_length' => 45,

        /*
         * The width of the print area in dots.
         * Adjust according to your needs.
         */
        'print_width' => 550,

        /*
         * The height (in dots) barcodes should be printed normally.
         */
        'barcode_height' => 64,

        /*
         * The width (magnification) each barcode should be printed in normally.
         */
        'barcode_width' => 2,
    ],
];
