<?php

namespace App\Service;

use App\Models\Discount;
use App\Models\DiscountSmsDelivery;
use App\Models\NextPurchaseDiscount;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Morilog\Jalali\Jalalian;

class NextPurchaseDiscountSmsScheduler
{
    public function __construct(private readonly SmsService $smsService)
    {
    }

    /**
     * Register deliveries for a new next-purchase discount.
     * Issued SMS is sent immediately; reminder stays pending until its day.
     */
    public function scheduleForDiscount(Discount $discount, ?string $mobile, ?string $name): void
    {
        $mobile = $this->smsService->normalizeMobile($mobile);
        if (!$mobile || !$discount->expires_at) {
            return;
        }

        $name = trim((string) $name);
        if ($name === '') {
            $name = 'مشتری گرامی';
        }

        $issuedBodyId = (int) config('services.payamak.body_ids.next_purchase_issued');
        $reminderBodyId = (int) config('services.payamak.body_ids.next_purchase_reminder');
        $reminderDays = (int) config('services.payamak.reminder_days_before_expiration', 4);

        $issued = DiscountSmsDelivery::firstOrCreate(
            [
                'discount_id' => $discount->id,
                'type' => DiscountSmsDelivery::TYPE_ISSUED,
            ],
            [
                'body_id' => $issuedBodyId,
                'recipient' => $mobile,
                'recipient_name' => $name,
                'scheduled_for' => now()->toDateString(),
                'status' => DiscountSmsDelivery::STATUS_PENDING,
                'attempts' => 0,
            ]
        );

        if ($issued->status === DiscountSmsDelivery::STATUS_PENDING) {
            $this->attemptSend($issued, $discount, now());
        }

        $reminderDate = $discount->expires_at->copy()->subDays($reminderDays)->startOfDay();
        if ($reminderDate->gte(now()->startOfDay())) {
            DiscountSmsDelivery::firstOrCreate(
                [
                    'discount_id' => $discount->id,
                    'type' => DiscountSmsDelivery::TYPE_REMINDER,
                ],
                [
                    'body_id' => $reminderBodyId,
                    'recipient' => $mobile,
                    'recipient_name' => $name,
                    'scheduled_for' => $reminderDate->toDateString(),
                    'status' => DiscountSmsDelivery::STATUS_PENDING,
                    'attempts' => 0,
                ]
            );
        }
    }

    public function dispatchDue(?Carbon $now = null): array
    {
        $now = $now ?? now();
        $startHour = (int) config('services.payamak.send_window_start_hour', 10);
        $endHour = (int) config('services.payamak.send_window_end_hour', 21);
        $maxAttempts = (int) config('services.payamak.max_attempts', 3);

        $stats = [
            'sent' => 0,
            'failed' => 0,
            'cancelled' => 0,
            'skipped' => 0,
        ];

        $settings = NextPurchaseDiscount::getLatestActive();
        // Only block when explicitly disabled; null/missing => enabled
        if ($settings && $settings->sms_enabled === false) {
            $cancelled = DiscountSmsDelivery::query()
                ->where('status', DiscountSmsDelivery::STATUS_PENDING)
                ->update([
                    'status' => DiscountSmsDelivery::STATUS_CANCELLED,
                    'last_response' => [
                        'reason' => 'sms_disabled',
                        'at' => $now->toDateTimeString(),
                    ],
                ]);
            $stats['cancelled'] += $cancelled;
            $stats['skipped'] = 1;

            return $stats;
        }

        if ($now->hour < $startHour || $now->hour > $endHour) {
            $stats['skipped'] = 1;
            return $stats;
        }

        $today = $now->toDateString();

        $overdueReminders = DiscountSmsDelivery::query()
            ->where('status', DiscountSmsDelivery::STATUS_PENDING)
            ->where('type', DiscountSmsDelivery::TYPE_REMINDER)
            ->whereDate('scheduled_for', '<', $today)
            ->update([
                'status' => DiscountSmsDelivery::STATUS_CANCELLED,
                'last_response' => [
                    'reason' => 'reminder_day_passed',
                    'at' => $now->toDateTimeString(),
                ],
            ]);
        $stats['cancelled'] += $overdueReminders;

        $deliveries = DiscountSmsDelivery::query()
            ->with('discount')
            ->where('status', DiscountSmsDelivery::STATUS_PENDING)
            ->where('attempts', '<', $maxAttempts)
            ->where(function ($query) use ($today) {
                $query->where(function ($issued) use ($today) {
                    $issued->where('type', DiscountSmsDelivery::TYPE_ISSUED)
                        ->whereDate('scheduled_for', '<=', $today);
                })->orWhere(function ($reminder) use ($today) {
                    $reminder->where('type', DiscountSmsDelivery::TYPE_REMINDER)
                        ->whereDate('scheduled_for', '=', $today);
                });
            })
            ->orderBy('id')
            ->get();

        foreach ($deliveries as $delivery) {
            $discount = $delivery->discount;

            if (!$discount || !$this->isDiscountEligible($discount, $now)) {
                $delivery->update([
                    'status' => DiscountSmsDelivery::STATUS_CANCELLED,
                    'last_response' => [
                        'reason' => 'discount_not_eligible',
                        'at' => $now->toDateTimeString(),
                    ],
                ]);
                $stats['cancelled']++;
                continue;
            }

            $outcome = $this->attemptSend($delivery, $discount, $now);
            $stats[$outcome]++;
        }

        return $stats;
    }

    /**
     * @return 'sent'|'failed'|'skipped'
     */
    protected function attemptSend(DiscountSmsDelivery $delivery, Discount $discount, Carbon $now): string
    {
        $maxAttempts = (int) config('services.payamak.max_attempts', 3);
        $result = $this->sendDelivery($delivery, $discount);
        $attempts = $delivery->attempts + 1;

        if ($result['success']) {
            $delivery->update([
                'status' => DiscountSmsDelivery::STATUS_SENT,
                'attempts' => $attempts,
                'provider_reference' => $result['rec_id'],
                'sent_at' => $now,
                'last_response' => $result,
            ]);

            return 'sent';
        }

        $failedPermanently = $attempts >= $maxAttempts;

        $delivery->update([
            'status' => $failedPermanently
                ? DiscountSmsDelivery::STATUS_FAILED
                : DiscountSmsDelivery::STATUS_PENDING,
            'attempts' => $attempts,
            'last_response' => $result,
        ]);

        if ($failedPermanently) {
            Log::error('Discount pattern SMS permanently failed', [
                'delivery_id' => $delivery->id,
                'discount_id' => $discount->id,
                'type' => $delivery->type,
                'result' => $result,
            ]);

            return 'failed';
        }

        Log::warning('Discount pattern SMS send failed, will retry', [
            'delivery_id' => $delivery->id,
            'discount_id' => $discount->id,
            'type' => $delivery->type,
            'attempts' => $attempts,
            'result' => $result,
        ]);

        return 'skipped';
    }

    protected function isDiscountEligible(Discount $discount, Carbon $now): bool
    {
        if (!$discount->is_active) {
            return false;
        }

        if ($discount->expires_at && $discount->expires_at->lte($now)) {
            return false;
        }

        if ($discount->usage_limit !== null && $discount->usage_count >= $discount->usage_limit) {
            return false;
        }

        return true;
    }

    protected function sendDelivery(DiscountSmsDelivery $delivery, Discount $discount): array
    {
        $name = $delivery->recipient_name ?: 'مشتری گرامی';

        if ($delivery->type === DiscountSmsDelivery::TYPE_ISSUED) {
            $amount = number_format((int) $discount->discount_value);
            $expires = $discount->expires_at
                ? Jalalian::fromCarbon($discount->expires_at)->format('Y/m/d')
                : '';

            // Pattern 499852: {0}=name, {1}=expires date, {2}=amount
            return $this->smsService->sendByBaseNumber2(
                $delivery->recipient,
                (int) $delivery->body_id,
                [$name, $expires, $amount]
            );
        }

        return $this->smsService->sendByBaseNumber2(
            $delivery->recipient,
            (int) $delivery->body_id,
            [$name]
        );
    }
}
