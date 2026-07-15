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
        'reminder_days_before_expiration',
        'discount_validity_days',
        'discount_sms_template',
        'reminder_sms_template',
        'profit_manager_ids',
        'target_customer_types',
    ];

    protected $casts = [
        'minimum_purchase_amount' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'is_active' => 'boolean',
        'days' => 'integer',
        'reminder_days_before_expiration' => 'integer',
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
    public function createDiscount(float $currentOrderAmount, int $customerId = null, $reserve_number = null): ?Discount
    {
        if (!$this->canApplyForCurrentOrder($currentOrderAmount)) {
            return null;
        }

        $code = $this->generateUniqueCode();
        $startsAt = now();
        $validityDays = $this->discount_validity_days ?? 7; // Default to 7 if not set
        $expiresAt =  now()->addDays($validityDays);

        $discountValue = intval(round(($this->discount_percentage / 100) * $currentOrderAmount));

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
            'discount_type' => 'fixed',
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
