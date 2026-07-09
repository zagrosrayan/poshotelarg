<?php

namespace App\Repository\Discount\Services;

use App\Models\Discount;
use App\Repository\Discount\Contracts\DiscountApplierInterface;
use App\Repository\Discount\DTO\ApplyDiscountDTO;

class DiscountApplierService implements DiscountApplierInterface
{
    public function apply(ApplyDiscountDTO $dto): array
    {
        $discount = $this->findValidDiscount($dto->code);

        $this->validateDiscount($discount, $dto);

        $discountAmount = $this->calculateDiscount(
            $discount->discount_type,
            $discount->discount_value,
            $dto->total_price
        );

        $finalPrice = $dto->total_price - $discountAmount;

        $this->updateDiscountUsage($discount);

        return [
            'final_price' => round($finalPrice, 2),
            'discount_amount' => round($discountAmount, 2),
            'discount_id' => $discount->id,
        ];
    }

    public function calculate(ApplyDiscountDTO $dto): array
    {
        $discount = $this->findValidDiscount($dto->code);

        $this->validateDiscount($discount, $dto);

        $discountAmount = $this->calculateDiscount(
            $discount->discount_type,
            $discount->discount_value,
            $dto->total_price
        );

        $finalPrice = $dto->total_price - $discountAmount;

        return [
            'final_price' => round($finalPrice, 2),
            'discount_amount' => round($discountAmount, 2),
            'discount_id' => $discount->id,
            'discount_type' => $discount->discount_type,
            'discount_value' => $discount->discount_value,
        ];
    }

    public function applyFinalize(string $discount_type, float $discount_value, float $totalPrice): array
    {
        $discountAmount = $this->calculateDiscount($discount_type, $discount_value, $totalPrice);

        return [
            'final_price' => round($totalPrice - $discountAmount, 2),
            'discount_amount' => $discountAmount,
        ];
    }

    private function findValidDiscount(string $code): Discount
    {
        $discount = Discount::where('code', $code)
            ->where('is_active', true)
            ->first();

        if (!$discount) {
            throw new \Exception('کد تخفیف معتبر نیست');
        }

        $this->validateDiscountDates($discount);

        return $discount;
    }

    private function validateDiscountDates(Discount $discount): void
    {
        if ($discount->is_unlimited) {
            return;
        }

        if ($discount->starts_at && $discount->starts_at->isFuture()) {
            throw new \Exception('این تخفیف هنوز شروع نشده است');
        }

        if ($discount->expires_at && $discount->expires_at->isPast()) {
            throw new \App\Exceptions\ExpiredDiscountException('این تخفیف منقضی شده است', 0, null, $discount);
        }
    }

    private function validateDiscount(Discount $discount, ApplyDiscountDTO $dto): void
    {
        $this->validateProfitManager($discount, $dto);
        $this->validateSpecialDiscount($discount, $dto);
        $this->validateCustomerDiscount($discount, $dto);
        $this->validateMinimumPrice($discount, $dto);
        $this->validateUsageLimit($discount);
        $this->validateDiscountConflicts($discount, $dto);
    }

    private function validateProfitManager(Discount $discount, ApplyDiscountDTO $dto): void
    {
        if ($dto->profit_manager_id !== null && $discount->profit_manager_ids && !in_array($dto->profit_manager_id, $discount->profit_manager_ids)) {
            throw new \Exception('امکان اعمال این تخفیف برای مرکز درآمد انتخابی وجود ندارد');
        }
    }

    private function validateSpecialDiscount(Discount $discount, ApplyDiscountDTO $dto): void
    {
        if (!$discount->is_special) {
            return;
        }

        if (empty($dto->reserve_number)) {
            throw new \Exception('این تخفیف تنها برای مشتریان مقیم قابل استفاده است. لطفا شماره رزرو را وارد نمایید');
        }

        if ($discount->reserve_number != $dto->reserve_number) {
            throw new \Exception('این تخفیف مخصوص مشتری مقیم دیگری است');
        }
    }

    private function validateCustomerDiscount(Discount $discount, ApplyDiscountDTO $dto): void
    {
        if (!$discount->customer_id) {
            return;
        }

        if (empty($dto->customer_id)) {
            throw new \Exception('برای استفاده از این تخفیف، انتخاب مشتری الزامی است');
        }

        if ($discount->customer_id != $dto->customer_id) {
            throw new \Exception('این تخفیف اختصاصی مشتری دیگری است');
        }
    }

    private function validateDiscountConflicts(Discount $discount, ApplyDiscountDTO $dto): void
    {
        if ($discount->is_special && $discount->customer_id) {
            throw new \Exception('این تخفیف دارای تنظیمات متناقض است');
        }

        if ($discount->reserve_number && $discount->customer_id) {
            throw new \Exception('این تخفیف دارای تنظیمات متناقض است');
        }
    }

    private function validateMinimumPrice(Discount $discount, ApplyDiscountDTO $dto): void
    {
        if (!$discount->minimum_price) {
            return;
        }

        if ($dto->total_price < $discount->minimum_price) {
            throw new \Exception('مبلغ سفارش کمتر از حداقل مجاز (' . number_format($discount->minimum_price) . ' ریال) است');
        }

        if ($discount->discount_type === 'fixed' && $discount->discount_value > $discount->minimum_price) {
            throw new \Exception('تنظیمات این تخفیف نامعتبر است');
        }
    }

    private function validateUsageLimit(Discount $discount): void
    {
        if ($discount->usage_limit === null) {
            return;
        }

        if ($discount->usage_count >= $discount->usage_limit) {
            throw new \Exception('این تخفیف به سقف تعداد استفاده رسیده است');
        }
    }

    private function updateDiscountUsage(Discount $discount): void
    {
        $discount->increment('usage_count');

        if ($discount->usage_limit !== null && $discount->usage_count >= $discount->usage_limit) {
            $discount->update(['is_active' => false]);
        }
    }

    private function calculateDiscount(string $type, float $value, float $total): float
    {
        if ($value <= 0) {
            throw new \Exception('مقدار تخفیف نامعتبر است');
        }

        if ($type === 'percentage') {
            if ($value > 100) {
                throw new \Exception('درصد تخفیف نمی‌تواند بیشتر از 100 باشد');
            }
            return min(round($total * ($value / 100), 2), $total);
        }

        if ($type === 'fixed') {
            return min($value, $total);
        }

        throw new \Exception('نوع تخفیف نامعتبر است');
    }
}