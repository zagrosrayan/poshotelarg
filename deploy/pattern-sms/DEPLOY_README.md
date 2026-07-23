# Fix: treat null sms_enabled as ON

## After unzip
php artisan migrate --force --path=database/migrations/2026_07_22_123000_add_sms_enabled_to_next_purchase_discounts_table.php
php artisan optimize:clear
