<?php

namespace App\Service;

use App\Models\Discount;

class DiscountService
{
    /**
     * ایجاد تخفیف جدید
     */
    public function createDiscount(array $data): Discount
    {
        return Discount::create($data);
    }

    /**
     * اعمال تخفیف
     */
    public function applyDiscount($code, $totalPrice, $userId = null, $productId = null)
    {
        $discount = Discount::where('code', $code)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->where('starts_at', '<=', now())
                    ->orWhereNull('starts_at');
            })
            ->where(function ($query) {
                $query->where('expires_at', '>=', now())
                    ->orWhereNull('expires_at');
            })
            ->first();

        // بررسی اعتبار کد تخفیف
        if (!$discount) {
            throw new \Exception('کد تخفیف معتبر نیست یا منقضی شده است.');
        }


        // بررسی حداقل مبلغ سفارش
        if ($discount->minimum_price && $totalPrice < $discount->minimum_price) {
            throw new \Exception("مبلغ سفارش ($totalPrice تومان) کمتر از حداقل مبلغ مجاز ({$discount->minimum_price} تومان) است.");
        }

        // بررسی محدودیت تعداد استفاده
        if ($discount->usage_limit !== null && $discount->usage_count >= $discount->usage_limit) {
            throw new \Exception('این کد تخفیف به حداکثر تعداد استفاده رسیده است.');
        }

        // محاسبه مبلغ تخفیف بر اساس نوع تخفیف
        if ($discount->discount_type === 'percentage') {
            $discountAmount = round($totalPrice * ($discount->discount_value / 100), 2);
        } elseif ($discount->discount_type === 'fixed') {
            $discountAmount = $discount->discount_value;

        } else {
            throw new \Exception('نوع تخفیف نامعتبر است.');
        }
        if ($discountAmount > $totalPrice){
            throw new \Exception('مقدار تخفیف محاسبه‌شده نمی‌تواند بیشتر از جمع کل سفارش باشد.');
        }
        // محاسبه قیمت نهایی پس از اعمال تخفیف
        $finalPrice = round($totalPrice - $discountAmount, 2);

        // به‌روزرسانی تعداد استفاده و غیرفعال کردن تخفیف در صورت نیاز
        $discount->increment('usage_count');
        if ($discount->usage_limit !== null && $discount->usage_count >= $discount->usage_limit) {
            $discount->update(['is_active' => false]);
        }

        // بازگشت مقادیر
        return [
            'final_price' => $finalPrice,
            'discount_amount' => $discountAmount,
        ];
    }
    public function applyDiscountInFinalizeOrder($discount_type,$discount_value,$totalPrice)
    {
        if ($discount_type === 'percentage') {
            $discountAmount = round($totalPrice * ($discount_value / 100), 2);
            // اطمینان از این‌که مبلغ تخفیف از مبلغ کل بیشتر نباشد
            $discountAmount = min($discountAmount, $totalPrice);
        } elseif ($discount_type === 'fixed') {
            $discountAmount = min($discount_value, $totalPrice); // مقدار ثابت تخفیف
        } else {
            throw new \Exception('نوع تخفیف نامعتبر است.');
        }

        // محاسبه قیمت نهایی پس از اعمال تخفیف
        $finalPrice = round($totalPrice - $discountAmount, 2);

        // بازگشت مقادیر
        return [
            'final_price' => $finalPrice,
            'discount_amount' => $discountAmount,
        ];
    }
}
