<?php

namespace App\Console\Commands;

use App\Models\Discount;
use App\Models\Food;
use App\Models\NextPurchaseDiscount;
use App\Models\Order;
use App\Models\ProfitManager;
use Illuminate\Console\Command;

class CheckNextPurchaseProfitCenters extends Command
{
    protected $signature = 'sms:check-profit-centers {order_id? : Optional order id to compare against settings}';

    protected $description = 'Compare next-purchase settings profit centers vs foods / a completed order';

    public function handle(): int
    {
        $this->info('=== All profit managers ===');
        $pms = ProfitManager::query()->orderBy('id')->get(['id', 'name', 'slug']);
        $this->table(['id', 'name', 'slug'], $pms->map(fn ($p) => [$p->id, $p->name, $p->slug])->all());

        $settings = NextPurchaseDiscount::getLatestActive();
        $this->info('=== Active next-purchase settings ===');
        if (!$settings) {
            $this->error('No active next_purchase_discounts row');
            return self::FAILURE;
        }

        $allowed = collect($settings->profit_manager_ids ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        $allowedNamed = $allowed->map(function ($id) use ($pms) {
            $name = $pms->firstWhere('id', $id)?->name ?? '?';
            return "{$id}:{$name}";
        })->all();

        $this->line('settings_id=' . $settings->id);
        $this->line('sms_enabled=' . var_export($settings->sms_enabled, true));
        $this->line('targets=' . json_encode($settings->target_customer_types, JSON_UNESCAPED_UNICODE));
        $this->line('profit_manager_ids=' . json_encode($allowed->all()));
        $this->line('profit_manager_names=' . json_encode($allowedNamed, JSON_UNESCAPED_UNICODE));

        if ($allowed->isEmpty()) {
            $this->warn('Empty profit_manager_ids means ALL centers are allowed by code.');
        }

        $orderId = $this->argument('order_id');
        if (!$orderId) {
            $orderId = Order::query()
                ->whereNull('parent_id')
                ->orderByDesc('id')
                ->value('id');
            $this->line('Using latest parent order_id=' . $orderId);
        }

        $order = Order::query()
            ->with(['customer:id,name,phone', 'children.food:id,name,profit_manager_id'])
            ->find($orderId);

        if (!$order) {
            $this->error("Order {$orderId} not found");
            return self::FAILURE;
        }

        $type = !empty($order->reserve_number) ? 'resident' : 'Non_resident';
        $this->info("=== Order {$order->id} ===");
        $this->line('invoice=' . $order->invoice_number);
        $this->line('customer_id=' . $order->customer_id . ' phone=' . ($order->customer?->phone ?? '-'));
        $this->line('reserve_number=' . ($order->reserve_number ?: '-'));
        $this->line('detected_type=' . $type);
        $this->line('total_price=' . $order->total_price);

        $rows = [];
        $orderPmIds = [];
        foreach ($order->children as $child) {
            $pmId = (int) ($child->food?->profit_manager_id ?? 0);
            if ($pmId > 0) {
                $orderPmIds[] = $pmId;
            }
            $pmName = $pms->firstWhere('id', $pmId)?->name ?? '-';
            $match = $allowed->isEmpty()
                ? true
                : ($pmId > 0 && $allowed->contains($pmId));
            $rows[] = [
                $child->food_id,
                $child->food?->name,
                $pmId ?: '-',
                $pmName,
                $match ? 'YES' : 'NO',
            ];
        }

        $this->table(['food_id', 'food', 'pm_id', 'pm_name', 'allowed_by_settings'], $rows);

        $orderPmIds = array_values(array_unique($orderPmIds));
        $intersection = $allowed->isEmpty()
            ? $orderPmIds
            : array_values(array_intersect($orderPmIds, $allowed->all()));

        $targetOk = empty($settings->target_customer_types)
            || in_array($type, $settings->target_customer_types, true);

        $this->info('=== Verdict ===');
        $this->line('order_profit_manager_ids=' . json_encode($orderPmIds));
        $this->line('intersection_with_settings=' . json_encode($intersection));
        $this->line('customer_type_allowed=' . ($targetOk ? 'YES' : 'NO'));
        $this->line('profit_center_allowed=' . ($intersection !== [] ? 'YES' : 'NO'));

        if (!$targetOk) {
            $this->error('BLOCKED: customer type not in settings targets');
        }
        if ($intersection === []) {
            $this->error('BLOCKED: no food profit center matches settings profit_manager_ids');
        }
        if ($targetOk && $intersection !== []) {
            $this->info('ELIGIBLE by type + profit centers (other skips like existing active NP still possible)');
        }

        // Latest NP for this customer
        if ($order->customer_id) {
            $latest = Discount::query()
                ->where('scope', 'next_purchase')
                ->where('customer_id', $order->customer_id)
                ->latest('id')
                ->first(['id', 'code', 'profit_manager_ids', 'created_at', 'usage_count', 'usage_limit', 'is_active']);
            $this->info('=== Latest NP discount for this customer ===');
            if ($latest) {
                $this->line(json_encode($latest->toArray(), JSON_UNESCAPED_UNICODE));
            } else {
                $this->warn('No next_purchase discount for this customer');
            }
        }

        // Sample: foods in allowed centers
        if ($allowed->isNotEmpty()) {
            $count = Food::query()->whereIn('profit_manager_id', $allowed->all())->count();
            $this->line("foods_in_allowed_centers_count={$count}");
        }

        return self::SUCCESS;
    }
}
