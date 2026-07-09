<?php

namespace App\Http\Requests;

use App\Models\Discount;
use App\Models\Food;
use App\Rules\CheckDiscountValid;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class CompleteOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $order = $this->route('order');
        $total_price = 0;
        foreach ($order?->children ?? [] as $item) {
            $food = Food::find($item->food_id);
            if ($food) {
                $total_price += intval(round($food->price * $item->quantity));
            }
        }

        return [
            'payment_method' => 'required|string|exists:types,id',
            'name' => 'nullable|string',
            'phone' => 'nullable|string',
            'serial_number' => 'nullable|string',
            'reserve_number' => 'nullable|string|exists:inhouseList,Reserve',
            'discount_normal_code' => [
                'nullable',
                'string',
                new CheckDiscountValid('normal'),
            ],
            'discount_global_code' => [
                'nullable',
                'string',
                new CheckDiscountValid('global'),
            ],
            'use_next_purchase_discount' => [
                'nullable',
                'boolean',
                function ($attribute, $value, $fail) use ($total_price) {
                    if ($value != true) {
                        return;
                    }
                    $customerId = $this->customer_id ?? $this->route('order')?->customer_id;
                    $reserveNumber = $this->reserve_number ?? $this->route('order')?->reserve_number;
                    $query = Discount::where('scope', 'next_purchase')
                        ->where('is_active', true)
                        ->where(function ($q) use ($customerId, $reserveNumber) {
                            if ($customerId) {
                                $q->where('customer_id', $customerId);
                            }
                            if ($reserveNumber) {
                                $q->orWhere('reserve_number', $reserveNumber);
                            }
                        })
                        ->whereColumn('usage_count', '<', 'usage_limit');
                    $discount = $query->first();
                    if (!$discount) {
                        $fail('تخفیف خرید بعدی فعالی برای شما یافت نشد');
                        return;
                    }
                    if ($discount->isExpired()) {
                        $fail('تخفیف خرید بعدی شما منقضی شده است|expired');
                        return;
                    }
                    if ($discount->minimum_price && $total_price < $discount->minimum_price) {
                        $fail('مبلغ سفارش کمتر از حداقل مبلغ مجاز برای استفاده از تخفیف خرید بعدی است');
                    }
                },
            ],
            'discount_type' => 'nullable|in:percentage,fixed',
            'discount_value' => [
                'nullable',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) use ($total_price) {
                    if (!$this->discount_type) {
                        return;
                    }
                    if ($this->discount_type === 'percentage') {
                        if ($value > 100) {
                            $fail('درصد تخفیف نمی‌تواند بیشتر از 100 باشد');
                            return;
                        }
                        $discountAmount = ($value / 100) * $total_price;
                        if ($discountAmount > $total_price) {
                            $fail('مقدار تخفیف محاسبه‌شده نمی‌تواند بیشتر از جمع کل سفارش باشد');
                        }
                    }
                    if ($this->discount_type === 'fixed' && $value > $total_price) {
                        $fail('مقدار تخفیف نمی‌تواند بیشتر از جمع کل سفارش باشد');
                    }
                },
            ],
            'use_club_points' => [
                'nullable',
                'boolean',
                function ($attribute, $value, $fail) {
                    if ($value == true) {
                        $hasReserveNumber = !empty($this->reserve_number) || !empty($this->route('order')?->reserve_number);
                        $hasCustomerId = !empty($this->customer_id) || !empty($this->route('order')?->customer_id);
                        if (!$hasReserveNumber && !$hasCustomerId) {
                            $fail('برای استفاده از امتیاز باشگاه مشتریان، باید شماره رزرو یا مشتری را انتخاب کنید');
                        }
                    }
                },
            ],
        ];
    }
    public function withValidator(Validator $validator)
    {
        $validator->after(function ($v) {
            $active = 0;
            if (!empty($this->discount_normal_code)) $active++;
            if (!empty($this->discount_global_code)) $active++;
            if ($this->boolean('use_next_purchase_discount')) $active++;
            if (!empty($this->discount_type) && !empty($this->discount_value)) $active++;
            if ($this->boolean('use_club_points')) $active++;
            if ($active > 1) {
                $v->errors()->add('discount', 'فقط یک نوع تخفیف قابل استفاده است در ثبت نهایی');
            }
        });
    }
    protected function failedValidation(Validator $validator)
    {
        $response = [
            'success' => false,
            'message' => 'خطا در اعتبارسنجی',
            'errors' => $validator->errors()->all(),
            'status' => 422,
        ];

        throw new ValidationException($validator, response()->json($response, 422));
    }
}
