# Queue and SMS production runbook

Next-purchase discount SMS now uses Melipayamak `SendByBaseNumber2` patterns
and a local scheduler (`sms:dispatch-discount-patterns` every 15 minutes).
No Laravel queue worker is required for automatic discount SMS.

Required cron:

```bash
* * * * * cd /path/to/hotel-pos-backend && php artisan schedule:run >> /dev/null 2>&1
```

Manual dry check:

```bash
php artisan sms:dispatch-discount-patterns
```

This document also covers legacy queue checks that cannot be performed from the
local source copy. Do not start the worker until pending SMS jobs have been
reviewed and removed.

## Diagnose Supervisor

Locate the real program configuration and inspect its logs:

```bash
sudo grep -Rni "laravel-queue" /etc/supervisor /etc/supervisord.conf 2>/dev/null
sudo supervisorctl status laravel-queue:*
sudo supervisorctl tail -100 laravel-queue:laravel-queue_00 stderr
```

Verify all paths and the runtime user from the discovered configuration:

```bash
command -v php
php -v
sudo -u <worker-user> /absolute/path/to/php /absolute/path/to/artisan about
sudo -u <worker-user> /absolute/path/to/php /absolute/path/to/artisan queue:work database --queue=default --once -vvv
```

PHP should be the intended PHP 8.3 CLI binary. The worker user must be able to
read the project and `.env`, and write to `storage` and
`bootstrap/cache`. Check that the Supervisor `directory`, PHP path and
`artisan` path are absolute and valid.

After correcting the configuration:

```bash
sudo supervisorctl reread
sudo supervisorctl update
```

Do not start the worker yet if old SMS jobs remain.

## Validate Laravel queue configuration

The deployed `.env` is expected to use the database queue. Validate the
effective configuration without printing secrets:

```bash
grep '^QUEUE_CONNECTION=' .env
php artisan about
php artisan config:show queue
php artisan queue:failed
php artisan schedule:list
```

The repository does not contain a migration that creates the `jobs` table.
Confirm that `jobs` and `failed_jobs` exist in the production database and
inspect their schemas before operating on them.

## Review and remove only SMS jobs

Stop the queue worker first to avoid processing or reserving rows while they
are being reviewed:

```bash
sudo supervisorctl stop laravel-queue:*
```

Do not use `queue:clear`, `queue:flush`, or an unfiltered `DELETE`. The default
queue also contains non-SMS work such as customer point upgrades and discount
deactivation.

First count matching pending jobs. Laravel JSON payloads contain escaped class
names, so confirm the matching syntax against the production database:

```sql
SELECT COUNT(*) AS sms_job_count
FROM jobs
WHERE payload LIKE '%SendOrderCompleteSms%'
   OR payload LIKE '%SendOrderWelcomeSms%'
   OR payload LIKE '%SendNextPurchaseDiscountToCustomers%';
```

Review metadata without selecting the full payload, which may contain phone
numbers or message text:

```sql
SELECT id, queue, attempts, reserved_at, available_at, created_at
FROM jobs
WHERE payload LIKE '%SendOrderCompleteSms%'
   OR payload LIKE '%SendOrderWelcomeSms%'
   OR payload LIKE '%SendNextPurchaseDiscountToCustomers%'
ORDER BY id;
```

Back up the database, confirm the result set, and then delete using exactly the
same predicate inside a transaction:

```sql
START TRANSACTION;

DELETE FROM jobs
WHERE payload LIKE '%SendOrderCompleteSms%'
   OR payload LIKE '%SendOrderWelcomeSms%'
   OR payload LIKE '%SendNextPurchaseDiscountToCustomers%';

SELECT ROW_COUNT() AS deleted_sms_jobs;
COMMIT;
```

Use the same review-first approach for `failed_jobs.payload` if failed SMS
records also need removal. Failed jobs cannot send unless retried, so retaining
them temporarily for audit is safer.

## Clear caches and restart

After deploying the code:

```bash
php artisan optimize:clear
```

Only rebuild caches after validating `.env` and application configuration:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

After pending SMS rows are gone and a one-shot worker check succeeds:

```bash
sudo supervisorctl start laravel-queue:*
sudo supervisorctl status laravel-queue:*
```

Complete a test order and verify that its next-purchase discount is stored,
while none of the three SMS class names appears in new `jobs` rows.

## Security incident

The previously observed hidden systemd paths, service, cron persistence and
remote shell execution are a separate high-severity incident. Preserve
evidence, rotate credentials from a trusted system, audit privileged access
and rebuild the host from known-good media if compromise cannot be ruled out.
Do not treat disabling the suspicious service as proof that the host is clean.
