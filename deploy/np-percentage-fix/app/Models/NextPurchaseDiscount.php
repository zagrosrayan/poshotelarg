<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Str;

class NextPurchaseDiscount extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'days',
        'code',
        'minimum_purchase_amount',
        'discount_percentage',
        'is_active',
        'sms_enabled',
        'discount_validity_days',
        'profit_manager_ids',
        'target_customer_types',
    ];

    protected $casts = [
        'minimum_purchase_amount' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'is_active' => 'boolean',
        'sms_enabled' => 'boolean',
        'days' => 'integer',
        'discount_validity_days' => 'integer',
        'profit_manager_ids' => 'array',
        'target_customer_types' => 'array',
    ];

    public static function getLatestActive()
    {
        return self::where('is_active', true)
            ->latest()
            ->first();
    }

    /**
     * ایجاد رکورد تخفیف واقعی (در جدول discounts) بر اساس تنظیمات فعلی.
     *
     * این متد فقط منطق ساخت تخفیف را انجام می‌دهد و فرض می‌کند که
     * اعتبار سفارش (حداقل مبلغ و فعال بودن) قبلاً بررسی شده است.
     */
    public function createDiscount(float $currentOrderAmount, ?int $customerId = null, $reserve_number = null): ?Discount
    {
        if (!$this->canApplyForCurrentOrder($currentOrderAmount)) {
            return null;
        }

        $code = $this->generateUniqueCode();
        $startsAt = now();
        $validityDays = $this->discount_validity_days ?? 7; // Default to 7 if not set
        $expiresAt =  now()->addDays($validityDays);

        // درصد تنظیمات عیناً روی خرید بعدی اعمال می‌شود (نه مبلغ ثابت از فاکتور فعلی)
        $discountValue = (float) $this->discount_percentage;

        return Discount::create([
            'name' => $this->name,
            'code' => $code,
            'discount_value' => $discountValue,
            'minimum_price' => (int) round((float) $this->minimum_purchase_amount),
            'customer_id' => $customerId ?? null,
            'reserve_number' => $reserve_number ?? null,
            'is_special' => $reserve_number ? true : false,
            'is_active' => true,
            'scope' => 'next_purchase',
            'discount_type' => 'percentage',
            'usage_limit' => 1,
            'usage_count' => 0,
            'starts_at' => $startsAt,
            'expires_at' => $expiresAt,
            'profit_manager_ids' => $this->profit_manager_ids,
        ]);
    }

    protected function generateUniqueCode(): string
    {
        do {
            $code = 'NP-' . strtoupper(Str::random(8));
        } while (Discount::where('code', $code)->exists());

        return $code;
    }

    public function canApplyForCurrentOrder(float $currentOrderAmount): bool
    {
        return $this->is_active && $currentOrderAmount >= $this->minimum_purchase_amount;
    }

    /**
     * آیا سفارش با تنظیمات طرح (حداقل مبلغ / مرکز درآمد / نوع مشتری) سازگار است؟
     * مستقل از «استفاده از تخفیف خرید بعدی» در چک‌اوت.
     */
    public function matchesOrder(Order $order): bool
    {
        if (!$this->canApplyForCurrentOrder((float) $order->total_price)) {
            return false;
        }

        if (!$this->matchesOrderProfitManagers($order)) {
            return false;
        }

        return $this->matchesCustomerType($order);
    }

    /**
     * مرکز درآمد: بر اساس اقلام سفارش (غذا)، نه کاربر صندوقدار.
     * آرایه خالی = همه مراکز.
     */
    public function matchesOrderProfitManagers(Order $order): bool
    {
        $allowed = array_values(array_unique(array_map(
            'intval',
            array_filter($this->profit_manager_ids ?? [], fn ($id) => $id !== null && $id !== '')
        )));

        if ($allowed === []) {
            return true;
        }

        $order->loadMissing('children.food');

        foreach ($order->children as $child) {
            $pmId = (int) ($child->food?->profit_manager_id ?? 0);
            if ($pmId > 0 && in_array($pmId, $allowed, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * نوع مشتری از وضعیت نهایی سفارش: رزرو = مقیم، در غیر این صورت غیرمقیم.
     * آرایه خالی = هر دو نوع.
     */
    public function matchesCustomerType(Order $order): bool
    {
        $allowed = $this->target_customer_types ?? [];
        if (!is_array($allowed) || $allowed === []) {
            return true;
        }

        $type = !empty($order->reserve_number) ? 'resident' : 'Non_resident';

        return in_array($type, $allowed, true);
    }

    public function getDiscountInfo(): array
    {
        return [
            'name' => $this->name,
            'discount_percentage' => $this->discount_percentage,
            'minimum_purchase_amount' => $this->minimum_purchase_amount,
            'valid_days' => $this->days,
        ];
    }

    /**
     * ساخت تخفیف برای مشتری/رزرو و برگرداندن اطلاعات مورد نیاز پاسخ API.
     */
    public function createDiscountForCustomer(?int $customerId, $reserveNumber, float $currentOrderAmount): array
    {
        if (!$this->is_active) {
            return [
                'success' => false,
                'message' => 'تنظیمات تخفیف خرید بعدی غیرفعال است',
            ];
        }

        if ($currentOrderAmount < $this->minimum_purchase_amount) {
            return [
                'success' => false,
                'message' => 'مبلغ سفارش کمتر از حداقل مبلغ تعریف شده است',
                'current_amount' => $currentOrderAmount,
                'required_amount' => $this->minimum_purchase_amount,
            ];
        }

        $discount = $this->createDiscount($currentOrderAmount, $customerId, $reserveNumber);

        if (!$discount) {
            return [
                'success' => false,
                'message' => 'امکان ایجاد تخفیف خرید بعدی وجود ندارد',
            ];
        }

        return [
            'success' => true,
            'code' => $discount->code,
            'discount_percentage' => $this->discount_percentage,
            'days' => $this->days,
            'expires_at' => $discount->expires_at,
            'discount' => $discount,
        ];
    }

    /**
     * یافتن تنظیمات تخفیف خرید بعدی که برای مبلغ سفارش فعلی قابل اعمال است.
     */
    public static function findEligibleDiscount(float $currentOrderAmount): ?self
    {
        return self::where('is_active', true)
            ->where('minimum_purchase_amount', '<=', $currentOrderAmount)
            ->orderByDesc('minimum_purchase_amount')
            ->first();
    }
}
