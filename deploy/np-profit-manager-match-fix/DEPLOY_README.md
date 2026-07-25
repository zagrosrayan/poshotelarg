# Fix: next-purchase discount profit-center matching

## Cause
Backend compared food profit center via relation (often null on SQL Server)
and did not normalize JSON string/int ids. Frontend payload was fine.

## After unzip
systemctl reload php8.3-fpm || systemctl reload php8.2-fpm || systemctl reload php-fpm
php artisan optimize:clear

## Verify on server
php artisan tinker --execute="echo json_encode(\App\Models\Food::find(2)?->only(['id','name','profit_manager_id']));"
php artisan tinker --execute="echo json_encode(\App\Models\Discount::where('scope','next_purchase')->where('customer_id',3178)->latest('id')->first()?->only(['id','code','profit_manager_ids']));"
