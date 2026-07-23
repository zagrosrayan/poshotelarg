<?php

namespace App\Console\Commands;

use App\Models\Discount;
use App\Models\DiscountSmsDelivery;
use App\Models\NextPurchaseDiscount;
use App\Models\Order;
use App\Models\Type;
use App\Http\Service\TypeSlug;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class InspectNextPurchaseDiscounts extends Command
{
    protected $signature = 'sms:inspect-next-purchase {--limit=30 : How many recent rows to show}';

    protected $description = 'Inspect next-purchase discounts and related SMS deliveries without DB client access';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $this->info('=== Next-purchase settings ===');
        $settingCols = ['id', 'name', 'is_active', 'minimum_purchase_amount', 'discount_percentage', 'discount_validity_days', 'profit_manager_ids', 'target_customer_types'];
        if (Schema::hasColumn('next_purchase_discounts', 'sms_enabled')) {
            $settingCols[] = 'sms_enabled';
        } else {
            $this->warn('Column sms_enabled does not exist yet');
        }

        $settings = NextPurchaseDiscount::query()->orderByDesc('id')->limit(5)->get($settingCols);

        if ($settings->isEmpty()) {
            $this->warn('No rows in next_purchase_discounts');
        } else {
            $this->table(
                ['id', 'name', 'is_active', 'sms_enabled', 'min_amount', 'percent', 'validity_days', 'profit_managers', 'targets'],
                $settings->map(fn ($s) => [
                    $s->id,
                    $s->name,
                    var_export($s->is_active, true),
                    Schema::hasColumn('next_purchase_discounts', 'sms_enabled')
                        ? var_export($s->sms_enabled, true)
                        : 'N/A',
                    $s->minimum_purchase_amount,
                    $s->discount_percentage,
                    $s->discount_validity_days,
                    json_encode($s->profit_manager_ids, JSON_UNESCAPED_UNICODE),
                    json_encode($s->target_customer_types, JSON_UNESCAPED_UNICODE),
                ])->all()
            );
        }

        $this->info('=== Issued next_purchase discounts (latest) ===');
        $discounts = Discount::query()
            ->with('customer:id,name,phone')
            ->where('scope', 'next_purchase')
            ->orderByDesc('id')
            ->limit($limit)
            ->get([
                'id', 'code', 'name', 'discount_value', 'customer_id', 'reserve_number',
                'is_active', 'usage_count', 'usage_limit', 'expires_at', 'created_at',
            ]);

        if ($discounts->isEmpty()) {
            $this->warn('No discounts with scope=next_purchase');
        } else {
            $this->table(
                ['id', 'code', 'value', 'customer', 'phone', 'reserve', 'active', 'used', 'expires_at', 'created_at'],
                $discounts->map(fn ($d) => [
                    $d->id,
                    $d->code,
                    $d->discount_value,
                    $d->customer_id,
                    $d->customer?->phone,
                    $d->reserve_number,
                    var_export((bool) $d->is_active, true),
                    ($d->usage_count ?? 0) . '/' . ($d->usage_limit ?? '-'),
                    optional($d->expires_at)?->toDateTimeString(),
                    optional($d->created_at)?->toDateTimeString(),
                ])->all()
            );
        }

        $this->info('=== Active unused next_purchase discounts ===');
        $active = Discount::query()
            ->with('customer:id,name,phone')
            ->where('scope', 'next_purchase')
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->whereColumn('usage_count', '<', 'usage_limit')
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['id', 'code', 'discount_value', 'customer_id', 'reserve_number', 'expires_at', 'created_at']);

        if ($active->isEmpty()) {
            $this->warn('No active unused next_purchase discounts');
        } else {
            $this->table(
                ['id', 'code', 'value', 'customer', 'phone', 'reserve', 'expires_at', 'created_at'],
                $active->map(fn ($d) => [
                    $d->id,
                    $d->code,
                    $d->discount_value,
                    $d->customer_id,
                    $d->customer?->phone,
                    $d->reserve_number,
                    optional($d->expires_at)?->toDateTimeString(),
                    optional($d->created_at)?->toDateTimeString(),
                ])->all()
            );
        }

        $this->info('=== SMS deliveries ===');
        if (!Schema::hasTable('discount_sms_deliveries')) {
            $this->error('Table discount_sms_deliveries does NOT exist');
        } else {
            $deliveries = DiscountSmsDelivery::query()
                ->orderByDesc('id')
                ->limit($limit)
                ->get([
                    'id', 'discount_id', 'type', 'recipient', 'status', 'attempts',
                    'body_id', 'provider_reference', 'sent_at', 'created_at',
                ]);

            if ($deliveries->isEmpty()) {
                $this->warn('discount_sms_deliveries is empty (SMS never queued/sent via ledger)');
            } else {
                $this->table(
                    ['id', 'discount_id', 'type', 'recipient', 'status', 'attempts', 'body_id', 'provider_ref', 'sent_at', 'created_at'],
                    $deliveries->map(fn ($s) => [
                        $s->id,
                        $s->discount_id,
                        $s->type,
                        $s->recipient,
                        $s->status,
                        $s->attempts,
                        $s->body_id,
                        $s->provider_reference,
                        optional($s->sent_at)?->toDateTimeString(),
                        optional($s->created_at)?->toDateTimeString(),
                    ])->all()
                );
            }
        }

        $this->info('=== Recent completed orders vs nearby next_purchase discount ===');
        $completeStatusId = Type::query()->where('slug', TypeSlug::ORDER_STATUS_COMPLETE)->value('id');
        $orders = Order::query()
            ->with('customer:id,name,phone')
            ->whereNull('parent_id')
            ->when($completeStatusId, fn ($q) => $q->where('status', $completeStatusId))
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['id', 'invoice_number', 'total_price', 'customer_id', 'reserve_number', 'status', 'updated_at', 'created_at']);

        if ($orders->isEmpty()) {
            $this->warn('No completed parent orders found');
        } else {
            $rows = [];
            foreach ($orders as $order) {
                $match = Discount::query()
                    ->where('scope', 'next_purchase')
                    ->where(function ($q) use ($order) {
                        if ($order->customer_id) {
                            $q->where('customer_id', $order->customer_id);
                        }
                        if ($order->reserve_number) {
                            $q->orWhere('reserve_number', $order->reserve_number);
                        }
                    })
                    ->whereBetween('created_at', [
                        optional($order->updated_at)?->copy()->subMinutes(15) ?? now()->subDay(),
                        optional($order->updated_at)?->copy()->addMinutes(15) ?? now(),
                    ])
                    ->orderByDesc('id')
                    ->first(['id', 'code', 'created_at']);

                $rows[] = [
                    $order->id,
                    $order->invoice_number,
                    $order->total_price,
                    $order->customer_id,
                    $order->customer?->phone,
                    $order->reserve_number,
                    optional($order->updated_at)?->toDateTimeString(),
                    $match?->id,
                    $match?->code,
                    optional($match?->created_at)?->toDateTimeString(),
                ];
            }

            $this->table(
                ['order_id', 'invoice', 'total', 'customer', 'phone', 'reserve', 'order_updated', 'np_discount_id', 'np_code', 'np_created'],
                $rows
            );
        }

        $this->info('Done. If np_discount_id is empty for recent completes, discount was not created on finalize.');
        $this->info('If discounts exist but SMS deliveries empty, SMS path did not run.');

        return self::SUCCESS;
    }
}
