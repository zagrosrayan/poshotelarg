# SMS on/off for next-purchase discount

Unzip into Laravel root.

## After unzip
php artisan migrate --force
php artisan optimize:clear

## Behavior
- sms_enabled=true: send issued/reminder pattern SMS
- sms_enabled=false: discount still created, SMS not sent
- PUT /v1/next-purchase-discount/{id} with { "sms_enabled": true|false }
