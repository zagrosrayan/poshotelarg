<?php

namespace App\Service;

use App\Models\Discount;
use App\Models\DiscountSmsDelivery;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Morilog\Jalali\Jalalian;

class NextPurchaseDiscountSmsScheduler
{
    public function __construct(private readonly SmsService $smsService)
    {
    }

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

        DiscountSmsDelivery::firstOrCreate(
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
                $stats['sent']++;
                continue;
            }

            $delivery->update([
                'status' => $attempts >= $maxAttempts
                    ? DiscountSmsDelivery::STATUS_FAILED
                    : DiscountSmsDelivery::STATUS_PENDING,
                'attempts' => $attempts,
                'last_response' => $result,
            ]);

            if ($attempts >= $maxAttempts) {
                Log::error('Discount pattern SMS permanently failed', [
                    'delivery_id' => $delivery->id,
                    'discount_id' => $discount->id,
                    'type' => $delivery->type,
                    'result' => $result,
                ]);
                $stats['failed']++;
            }
        }

        return $stats;
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

            return $this->smsService->sendByBaseNumber2(
                $delivery->recipient,
                (int) $delivery->body_id,
                [$name, $amount, $expires]
            );
        }

        return $this->smsService->sendByBaseNumber2(
            $delivery->recipient,
            (int) $delivery->body_id,
            [$name]
        );
    }
}
