# Hotfix: missing SmsService::normalizeMobile on server

Root cause from laravel.log:
Call to undefined method App\Service\SmsService::normalizeMobile()

Scheduler was updated but SmsService.php on server was old.

## After unzip into Laravel root
php artisan migrate --force --path=database/migrations/2026_07_21_173000_create_discount_sms_deliveries_table.php
php artisan migrate --force --path=database/migrations/2026_07_22_123000_add_sms_enabled_to_next_purchase_discounts_table.php
php artisan optimize:clear

## Verify method exists
php -r "require 'vendor/autoload.php'; \=require 'bootstrap/app.php'; \->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); echo method_exists(app(App\Service\SmsService::class),'normalizeMobile') ? 'OK' : 'MISSING';"
