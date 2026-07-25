# Deploy: Melipayamak pattern IDs + reminder variables

## What this zip updates
- Pattern issued: 500571 (name, amount, expires)
- Pattern reminder: 500779 (name, amount)
- Reminder window: 2 days before expiration
- Diagnose command now tests both patterns

## Critical: update server .env
PAYAMAK_BODY_ID_NEXT_PURCHASE=500571
PAYAMAK_BODY_ID_NEXT_PURCHASE_REMINDER=500779

## After unzip into Laravel root
systemctl reload php8.3-fpm || systemctl reload php8.2-fpm || systemctl reload php-fpm
php artisan optimize:clear
php artisan config:clear
php artisan sms:diagnose
# optional live test both patterns:
# php artisan sms:diagnose --send --to=09xxxxxxxxx

## Verify scheduler args
grep -n "500571\|500779\|number_format\|reminder_days_before_expiration" app/Service/NextPurchaseDiscountSmsScheduler.php config/services.php
