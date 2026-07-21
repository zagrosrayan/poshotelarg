# Pattern SMS deploy package

Unzip this archive into the Laravel project root so paths like
`app/Service/SmsService.php` replace existing files.

## After unzip

```bash
php artisan migrate --force
php artisan optimize:clear
```

Add cron if missing:

```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

## Delete these obsolete job files if they still exist

- `app/Jobs/SendOrderCompleteSms.php`
- `app/Jobs/SendOrderWelcomeSms.php`
- `app/Jobs/SendNextPurchaseDiscountToCustomers.php`

## Notes

- Automatic next-purchase SMS uses Payamak `SendByBaseNumber2`
  - issued bodyId `499216`
  - reminder bodyId `499219`
- Scheduler command: `sms:dispatch-discount-patterns` (every 15 minutes, send window 10:00-21:59)
- Manual customer-report SMS still uses `SendSimpleSMS2`
