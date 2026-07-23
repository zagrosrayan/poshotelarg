<?php

namespace App\Console\Commands;

use App\Models\DiscountSmsDelivery;
use App\Models\NextPurchaseDiscount;
use App\Service\SmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class DiagnoseDiscountSms extends Command
{
    protected $signature = 'sms:diagnose
                            {--send : Also send a live test SMS to --to}
                            {--to=09107860475 : Mobile for live test}';

    protected $description = 'Diagnose next-purchase pattern SMS deployment and optionally send a test';

    public function handle(SmsService $sms): int
    {
        $this->info('=== Discount SMS diagnose ===');
        $this->line('time: ' . now()->toDateTimeString() . ' tz=' . config('app.timezone'));
        $this->line('php: ' . PHP_VERSION);
        $this->line('SmsService file: ' . (new \ReflectionClass(SmsService::class))->getFileName());

        $checks = [
            'normalizeMobile' => method_exists($sms, 'normalizeMobile'),
            'sendByBaseNumber2' => method_exists($sms, 'sendByBaseNumber2'),
            'parseProviderResult' => method_exists($sms, 'parseProviderResult'),
        ];

        foreach ($checks as $method => $ok) {
            $this->{$ok ? 'info' : 'error'}(($ok ? '[OK] ' : '[MISSING] ') . "SmsService::{$method}()");
        }

        $issued = (int) config('services.payamak.body_ids.next_purchase_issued');
        $reminder = (int) config('services.payamak.body_ids.next_purchase_reminder');
        $this->line("body_ids: issued={$issued} reminder={$reminder}");

        $user = (string) env('API_USERNAME_MELI_PAYAMAK');
        $pass = (string) env('API_KEY_MELI_PAYAMAK');
        $from = (string) env('API_FROM_MELI_PAYAMAK');
        $this->line('credentials: username=' . ($user !== '' ? 'set' : 'EMPTY')
            . ' password=' . ($pass !== '' ? 'set' : 'EMPTY')
            . ' from=' . ($from !== '' ? $from : 'EMPTY'));

        $hasSmsEnabledCol = Schema::hasColumn('next_purchase_discounts', 'sms_enabled');
        $this->{$hasSmsEnabledCol ? 'info' : 'warn'}(
            ($hasSmsEnabledCol ? '[OK] ' : '[WARN] ') . 'column next_purchase_discounts.sms_enabled'
        );

        $hasDeliveries = Schema::hasTable('discount_sms_deliveries');
        $this->{$hasDeliveries ? 'info' : 'error'}(
            ($hasDeliveries ? '[OK] ' : '[MISSING] ') . 'table discount_sms_deliveries'
        );

        $settings = NextPurchaseDiscount::getLatestActive();
        if (!$settings) {
            $this->warn('[WARN] no active next_purchase_discounts row');
        } else {
            $enabled = $settings->sms_enabled;
            $this->line(sprintf(
                'active setting id=%s name=%s sms_enabled=%s',
                $settings->id,
                $settings->name,
                var_export($enabled, true)
            ));
            if ($enabled === false) {
                $this->error('[BLOCKED] sms_enabled is false — SMS will not send');
            }
        }

        if ($hasDeliveries) {
            $rows = DiscountSmsDelivery::query()
                ->orderByDesc('id')
                ->limit(10)
                ->get(['id', 'discount_id', 'type', 'recipient', 'status', 'attempts', 'body_id', 'provider_reference', 'sent_at', 'created_at']);

            if ($rows->isEmpty()) {
                $this->warn('[WARN] no rows in discount_sms_deliveries yet');
            } else {
                $this->info('latest discount_sms_deliveries:');
                $this->table(
                    ['id', 'discount_id', 'type', 'recipient', 'status', 'attempts', 'body_id', 'provider_reference', 'sent_at', 'created_at'],
                    $rows->map(fn ($r) => [
                        $r->id,
                        $r->discount_id,
                        $r->type,
                        $r->recipient,
                        $r->status,
                        $r->attempts,
                        $r->body_id,
                        $r->provider_reference,
                        optional($r->sent_at)?->toDateTimeString(),
                        optional($r->created_at)?->toDateTimeString(),
                    ])->all()
                );
            }
        }

        if (!$checks['normalizeMobile'] || !$checks['sendByBaseNumber2']) {
            $this->error('Server still has old SmsService. Replace app/Service/SmsService.php and reload PHP-FPM.');
            return self::FAILURE;
        }

        if ($this->option('send')) {
            $to = (string) $this->option('to');
            $this->info("Sending live issued pattern to {$to} ...");
            $result = $sms->sendByBaseNumber2($to, $issued, ['تست سیستم', now()->format('Y/m/d'), '150000']);
            $this->line(json_encode($result, JSON_UNESCAPED_UNICODE));
            if (!($result['success'] ?? false)) {
                return self::FAILURE;
            }
            $this->info('Live send OK');
        }

        $this->info('Diagnose finished.');
        return self::SUCCESS;
    }
}
