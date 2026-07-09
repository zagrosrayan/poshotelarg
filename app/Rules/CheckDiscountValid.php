<?php

namespace App\Rules;

use App\Models\Discount;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CheckDiscountValid implements ValidationRule
{
    public $discount_type;
    public function __construct($discount_type){
     $this->discount_type = $discount_type;
    }
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $discount = Discount::findByCode($value);

        if (!$discount) {
            $fail('کد تخفیف وارد شده وجود ندارد');
            return;
        }

        if (!$discount->is_active) {
             $fail('کد تخفیف وارد شده فعال نیست');
             return;
        }

        if ($discount->isExpired()) {
             $fail('کد تخفیف وارد شده منقضی شده است|expired');
             return;
        }

        if ($this->discount_type == 'global' && !$discount->isGlobal()) {
            $fail('کد تخفیف وارد شده همگانی نیست و در بخش تخفیف همگانی قابل استفاده نیست');
        }
        if ($this->discount_type == 'normal' && !$discount->isNormal()) {
            $fail('کد تخفیف وارد شده ساده نیست و در بخش تخفیف ساده قابل استفاده نیست');
        }
    }
}
