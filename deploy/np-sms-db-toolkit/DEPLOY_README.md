# Next-purchase SMS DB toolkit (one package)

Includes:
1) Safe schema migration (additive only)
2) sms:check-schema
3) sms:heal-np-data (dry-run by default)
4) Related backend fixes already needed on server:
   - SQL Server safe SMS disable (last_response JSON encode)
   - Food profit_manager_id matching for NP apply

## Deploy
Unzip into Laravel root (/var/www/html/protel), then:

systemctl reload php8.3-fpm || systemctl reload php8.2-fpm || systemctl reload php-fpm
php artisan optimize:clear

## 1) Schema (safe)
php artisan migrate --path=database/migrations/2026_07_25_190000_ensure_next_purchase_sms_schema_compatible.php --force

## 2) Verify
php artisan sms:check-schema

## 3) Optional data heal (review first)
php artisan sms:heal-np-data
# php artisan sms:heal-np-data --force
# php artisan sms:heal-np-data --force --sync-codes

## Safety notes
- Migration never drops/changes types
- Migration never mass-updates issued discount codes
- heal-np-data writes only with --force
