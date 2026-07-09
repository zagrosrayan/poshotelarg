<?php

namespace App\Http\Requests;

use App\Models\Discount;
use App\Models\Food;
use App\Rules\CheckDiscountValid;
use App\Rules\DescNumberRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $data = [];

        if ($this->has('customer_id') && !is_null($this->customer_id)) {
            $data['customer_id'] = intval($this->customer_id);
        }

        if (!empty($data)) {
            $this->merge($data);
        }
    }
    public function rules(): array
    {
        $total_price = 0;
        foreach ($this->order ?? [] as $order) {
            $food = Food::find($order['food_id']);
            if ($food) {
                $total_price += intval(round($food->price * $order['quantity']));
            }
        }

        return [
            'rate_service' => 'required|in:0,1',
            'service_type' => 'required|in:takeaway,dine_in,room_service',
            'desc_number' => [
                'nullable',
                'string',
                'required_if:service_type,dine_in',
                Rule::when(
                    fn() => $this->service_type !== 'takeaway' && $this->service_type !== 'room_service',
                    [new DescNumberRule()]
                )
            ],
            'reserve_number' => [
                'nullable',
                'string',
                'exists:InhouseList,Reserve'
            ],
            'discount_normal_code' => [
                'nullable',
                'string',
                new CheckDiscountValid('normal')
            ],
            'discount_global_code' => [
                'nullable',
                'string',
                new CheckDiscountValid('global')
            ],
            'use_next_purchase_discount' => [
                'nullable',
                'boolean',
                function ($attribute, $value, $fail) use ($total_price) {
                    if ($value != true) {
                        return;
                    }

                    $query = Discount::where('scope', 'next_purchase')
                        ->where('is_active', true)
                        ->where(function ($q) {
                            if ($this->customer_id) {
                                $q->where('customer_id', $this->customer_id);
                            }
                            if ($this->reserve_number) {
                                $q->orWhere('reserve_number', $this->reserve_number);
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
                }
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
                        $hasReserveNumber = !empty($this->reserve_number);
                        $hasCustomerId = !empty($this->customer_id);

                        if (!$hasReserveNumber && !$hasCustomerId) {
                            $fail('برای استفاده از امتیاز باشگاه مشتریان، باید شماره رزرو یا مشتری را انتخاب کنید');
                        }
                    }
                }
            ],
            'customer_id' => [
                'nullable',
                'integer',
                'exists:customers,id'
            ],
            'customer_name' => [
                'nullable',
                'string',
            ],
            'customer_mobile' => [
                'nullable',
                'string',
                Rule::unique('customers', 'phone')
            ],
            'order' => 'required|array',
            'order.*.quantity' => 'required|integer|min:1',
            'order.*.food_id' => [
                'required',
                'integer',
                Rule::exists('food', 'id')->whereNull('deleted_at'),
            ],
            'order.*.description' => 'nullable|string',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $discountFields = [
                'discount_normal_code' => $this->discount_normal_code,
                'discount_global_code' => $this->discount_global_code,
                'use_next_purchase_discount' => $this->use_next_purchase_discount,
                'discount_value' => $this->discount_value,
                'use_club_points' => $this->use_club_points
            ];

            $activeDiscounts = collect($discountFields)->filter(fn($value) => !empty($value))->count();
            if (!empty($this->customer_id) and (!empty($this->customer_mobile) || !empty($this->reserve_number)) ) {
                $validator->errors()->add('customer_id','فقط یک نوع کاربر قابل انتخاب است');
            }

            if ($activeDiscounts > 1) {
                $validator->errors()->add('discount', 'فقط یک نوع تخفیف قابل استفاده است');
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