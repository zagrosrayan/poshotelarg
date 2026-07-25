# Schema check + SMS disable SQL Server fix

## 1) Unzip into Laravel root, reload PHP
systemctl reload php8.3-fpm || systemctl reload php8.2-fpm || systemctl reload php-fpm
php artisan optimize:clear

## 2) Check DB structure
php artisan sms:check-schema

Paste the full output. FAIL lines mean missing table/column.
