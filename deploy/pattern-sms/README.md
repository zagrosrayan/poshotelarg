# Order-complete SMS fix + warning-level logs

## Critical deploy check
grep -n issueNextPurchaseDiscountSms app/Http/Controllers/OrderController.php

Must print a line number. If empty, zip was not applied.

## After unzip
systemctl reload php8.3-fpm || systemctl reload php8.2-fpm || systemctl reload php-fpm
php artisan optimize:clear

## After one complete-order
grep -i next_purchase_sms storage/logs/laravel.log | tail -n 30
# or daily log:
ls -lt storage/logs/
grep -i next_purchase_sms storage/logs/laravel-*.log | tail -n 30
