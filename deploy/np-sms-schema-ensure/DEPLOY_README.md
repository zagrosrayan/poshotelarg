# SAFE DB ensure for next-purchase SMS

## Migration (schema only — production safe)
- Adds missing columns/tables only
- Never drops / never changes types
- Never mass-updates discount codes
- Only sets sms_enabled where it is NULL
- Fails if core tables discounts / next_purchase_discounts are missing

php artisan migrate --path=database/migrations/2026_07_25_190000_ensure_next_purchase_sms_schema_compatible.php --force
php artisan sms:check-schema

## Optional DATA heal (default dry-run)
php artisan sms:heal-np-data
php artisan sms:heal-np-data --force
php artisan sms:heal-np-data --force --sync-codes
