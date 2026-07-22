# Pattern SMS — changed files only

Unzip into the Laravel project root (paths are relative to root).

## Files in this zip

- `app/Service/NextPurchaseDiscountSmsScheduler.php` — immediate issued SMS + var order
- `config/services.php` — default issued bodyId `499852`
- `.env.example` — `PAYAMAK_BODY_ID_NEXT_PURCHASE=499852`
- `docs/queue-sms-runbook.md`
- `tests/Feature/HotelArgFeaturesTest.php`

## After unzip

In `.env` set (or update):

```env
PAYAMAK_BODY_ID_NEXT_PURCHASE=499852
PAYAMAK_BODY_ID_NEXT_PURCHASE_REMINDER=499219
```

Then:

```bash
php artisan optimize:clear
```

Issued SMS uses pattern `499852` and is sent immediately when the discount is created.
Reminder still uses `499219`.
