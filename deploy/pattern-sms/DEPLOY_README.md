# SMS hotfix + diagnose

## MUST after unzip
1) replace files
2) reload PHP-FPM (opcache often keeps old SmsService)
3) clear laravel caches
4) run diagnose

`ash
cd /var/www/html/protel
# unzip here

# pick your php-fpm service name:
systemctl reload php8.3-fpm || systemctl reload php8.2-fpm || systemctl reload php-fpm

php artisan migrate --force --path=database/migrations/2026_07_21_173000_create_discount_sms_deliveries_table.php
php artisan migrate --force --path=database/migrations/2026_07_22_123000_add_sms_enabled_to_next_purchase_discounts_table.php
php artisan optimize:clear

# check deployment
php artisan sms:diagnose

# live send test
php artisan sms:diagnose --send --to=09107860475
`

If diagnose says MISSING normalizeMobile, SmsService.php was not replaced or PHP-FPM was not reloaded.
