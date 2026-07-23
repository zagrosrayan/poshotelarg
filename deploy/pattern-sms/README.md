# Fix: order-complete SMS path

## What was wrong
1) customer relation not refreshed after setting customer_id => mobile empty
2) discount/SMS ran AFTER printer; printer failure skipped SMS
3) no skip-reason logs

## After unzip
systemctl reload php8.3-fpm || systemctl reload php8.2-fpm || systemctl reload php-fpm
php artisan optimize:clear

## After a real complete-order test
grep -i next_purchase_sms storage/logs/laravel.log | tail -n 30
php artisan sms:diagnose
