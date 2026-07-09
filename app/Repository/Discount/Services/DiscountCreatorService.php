<?php

namespace App\Repository\Discount\Services;

use App\Models\Discount;
use App\Repository\Discount\Contracts\DiscountCreatorInterface;
use App\Repository\Discount\DTO\DiscountCreateDTO;
use App\Repository\Discount\DTO\DiscountUpdateDTO;

class DiscountCreatorService implements DiscountCreatorInterface
{
    public function create(DiscountCreateDTO $dto): Discount
    {
        $this->validateDiscountCreation($dto);

        return Discount::create($dto->toArray());
    }

    public function update(int $id, DiscountUpdateDTO $dto): Discount
    {
        $discount = Discount::findOrFail($id);

        $this->validateDiscountUpdate($discount, $dto);

        $discount->update($dto->toArray());

        return $discount->fresh();
    }

    private function validateDiscountCreation(DiscountCreateDTO $dto): void
    {
        $this->validateDiscountCode($dto->code);
        $this->validateDiscountDates($dto->starts_at, $dto->expires_at);
        $this->validateDiscountValue($dto->discount_type, $dto->discount_value);
        $this->validateSpecialDiscountRequirements($dto->is_special, $dto->reserve_number);
        $this->validateDiscountConflicts($dto->is_special, $dto->customer_id, $dto->reserve_number);
        $this->validateMinimumPrice($dto->minimum_price, $dto->discount_value, $dto->discount_type);
        $this->validateDiscountUnlimited($dto);
    }

    private function validateDiscountUnlimited(DiscountCreateDTO $dto): void
    {
        if ($dto->is_unlimited && ($dto->starts_at !== null || $dto->expires_at !== null)) {
            throw new \Exception('برای تخفیف نامحدود نمی‌توانید تاریخ شروع یا پایان تعیین کنید.');
        }
        if ($dto->is_unlimited == false && ($dto->starts_at == null || $dto->expires_at == null)) {
            throw new \Exception('تخفیف باید یا نامحدود باشد یا بازه زمانی معتبر داشته باشد.');
        }
    }
    private function validateDiscountUpdate(Discount $discount, DiscountUpdateDTO $dto): void
    {
        $this->validateDiscountDates($dto->starts_at, $dto->expires_at);
        $this->validateDiscountValue($dto->discount_type, $dto->discount_value);
        $this->validateSpecialDiscountRequirements($dto->is_special, $dto->reserve_number);
        $this->validateDiscountConflicts($dto->is_special, $dto->customer_id, $dto->reserve_number);
        $this->validateMinimumPrice($dto->minimum_price, $dto->discount_value, $dto->discount_type);
    }

    private function validateDiscountCode(string $code): void
    {
        $exists = Discount::where('code', $code)->exists();

        if ($exists) {
            throw new \Exception('این کد تخفیف قبلا ثبت شده است. لطفا کد دیگری انتخاب کنید');
        }
    }

    private function validateDiscountDates(?string $startsAt, ?string $expiresAt): void
    {
        if ($startsAt && strtotime($startsAt) < strtotime('today')) {
            throw new \Exception('تاریخ شروع تخفیف نمی‌تواند در گذشته باشد');
        }

        if ($expiresAt && strtotime($expiresAt) < strtotime('today')) {
            throw new \Exception('تاریخ انقضای تخفیف نمی‌تواند در گذشته باشد');
        }

        if ($startsAt && $expiresAt && strtotime($expiresAt) < strtotime($startsAt)) {
            throw new \Exception('تاریخ انقضا نمی‌تواند قبل از تاریخ شروع باشد');
        }
    }

    private function validateDiscountValue(string $discountType, float $discountValue): void
    {
        if ($discountValue <= 0) {
            throw new \Exception('مقدار تخفیف باید بیشتر از صفر باشد');
        }

        if ($discountType === 'percentage' && $discountValue > 100) {
            throw new \Exception('درصد تخفیف نمی‌تواند بیشتر از ۱۰۰ باشد');
        }
    }

    private function validateSpecialDiscountRequirements(bool $isSpecial, ?int $reserveNumber): void
    {
        if ($isSpecial && empty($reserveNumber)) {
            throw new \Exception('برای تخفیف ویژه مشتریان مقیم، وارد کردن شماره رزرو الزامی است');
        }
    }

    private function validateDiscountConflicts(bool $isSpecial, ?int $customerId, ?int $reserveNumber): void
    {
        if ($isSpecial && $customerId) {
            throw new \Exception('تخفیف نمی‌تواند هم‌زمان برای مشتری مقیم و مشتری خاص تعریف شود');
        }

        if ($reserveNumber && $customerId) {
            throw new \Exception('تخفیف نمی‌تواند هم‌زمان شماره رزرو و مشتری داشته باشد');
        }
    }

    private function validateMinimumPrice(?float $minimumPrice, float $discountValue, string $discountType): void
    {
        if (!$minimumPrice) {
            return;
        }

        if ($discountType === 'fixed' && $discountValue > $minimumPrice) {
            throw new \Exception('مقدار تخفیف نمی‌تواند بیشتر از حداقل مبلغ خرید باشد');
        }
    }
}