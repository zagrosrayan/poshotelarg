# Deploy: next-purchase as percentage + SMS var fix

## Changes
1. NP codes are now percentage (settings %) applied on NEXT order — not fixed rial from previous invoice.
2. SMS amount vars no longer use thousands separators (commas break Melipayamak SendByBaseNumber2).

## Melipayamak pattern text
Body vars for issued/reminder amount are now the percent number (e.g. 10).
If pattern text still says "ریال", update it to "درصد" in Melipayamak panel.

## After unzip
systemctl reload php8.3-fpm || systemctl reload php8.2-fpm || systemctl reload php-fpm
php artisan optimize:clear
