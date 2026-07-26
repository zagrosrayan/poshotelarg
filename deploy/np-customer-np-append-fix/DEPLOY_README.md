# Fix: customer next_purchase_discount_code missing on SQL Server

Customer append used whereColumn(usage_count, usage_limit) which can fail to match on sqlsrv.
Switched to whereRaw so POS UI sees existing NP codes.

php artisan optimize:clear
