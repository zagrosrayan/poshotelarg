<?php

namespace App\Rules;

use App\Models\ProfitManager;
use Closure;
use App\Models\Order;
use App\Models\Type;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Http\Service\TypeSlug;
use Illuminate\Support\Facades\Auth;

class DescNumberRule implements ValidationRule
{
    protected $orderId;

    public function __construct($orderId = null)
    {
        $this->orderId = $orderId;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return;
        }

        $pendingStatus = Type::where('slug', TypeSlug::ORDER_STATUS_PENDING)->first();

        if (!$pendingStatus) {
            return;
        }

        $query = Order::where('desc_number', $value)  // بدون intval
        ->where('status', $pendingStatus->id)
            ->whereHas('children', function ($childQuery) {
                $childQuery->whereHas('food', function ($foodQuery) {
                    $foodQuery->where('profit_manager_id', auth()->user()->profit_manager_id);
                });
            });

        if ($this->orderId) {
            $query->where('id', '!=', $this->orderId);
        }

        if ($query->exists()) {
            $fail('این میز در حال حاضر توسط سفارش دیگری اشغال شده است.');
        }
    }
}