# Fix: disable SMS on SQL Server

Bug: mass-updating last_response with a PHP array caused
"Array to string conversion" on sqlsrv.

After unzip into Laravel root:
systemctl reload php8.3-fpm || systemctl reload php8.2-fpm || systemctl reload php-fpm
php artisan optimize:clear
