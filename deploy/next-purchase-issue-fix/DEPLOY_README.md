# Deploy: next-purchase issue logic fix

## What was broken
1. Cash/POS complete for room orders cleared reserve on the model but put reserve_number back from request payload → customer typed as resident forever.
2. Profit-center check used cashier user.profit_manager_id instead of food items on the order.

## Correct business flow (this patch)
After order COMPLETE:
- If order matches next-purchase SETTINGS (min amount + profit centers on foods + customer type) → create NP discount for that customer
- If sms_enabled → send pattern SMS
- Checkout toggle use_next_purchase_discount is unrelated (only consumes an existing NP code on this invoice)

## After unzip into Laravel root
systemctl reload php8.3-fpm || systemctl reload php8.2-fpm || systemctl reload php-fpm
php artisan optimize:clear

## Settings reminder
In admin next-purchase settings, select the customer types and profit centers you really want.
Current prod row was Non_resident + profit_manager [4] only — room guests need "resident" checked too.

## Verify after one complete
grep -E "next_purchase_discount_created|next_purchase_sms_dispatch|next_purchase_sms_skip" storage/logs/laravel*.log | tail -n 40
php artisan sms:inspect-next-purchase --limit=5
