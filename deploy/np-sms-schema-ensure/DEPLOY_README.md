# Ensure next-purchase + SMS DB schema (SQL Server / MySQL)

## What it does
- Creates missing tables/columns for next_purchase_discounts, discounts, discount_sms_deliveries
- Sets sms_enabled default where null
- If active settings have empty profit centers, fills all profit_managers
- Syncs active unused NP discount codes to current settings profit_manager_ids
- Asserts required schema at end (fails migrate if still incomplete)

## Deploy
1. Copy migration file into database/migrations/
2. Run:

php artisan migrate --force
php artisan sms:check-schema

Or only this file:

php artisan migrate --path=database/migrations/2026_07_25_190000_ensure_next_purchase_sms_schema_compatible.php --force
