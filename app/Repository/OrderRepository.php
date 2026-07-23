<?php

namespace App\Repository;

use App\Models\Discount;
use App\Models\Food;
use App\Models\Setting;
use App\Repository\Discount\Contracts\DiscountApplierInterface;
use App\Repository\Discount\DTO\ApplyDiscountDTO;

class OrderRepository
{
    protected $discountApplier;

    public function __construct(DiscountApplierInterface $discountApplier)
    {
        $this->discountApplier = $discountApplier;
    }

    public function calculatePrice(
        $orderDetails,
        $totalPrice,
        $discount_code,
        $discount_value,
        $discount_type,
        $rate_service,
        $reserve_number,
        $customer_id,
        $is_special,
        $use_club_points = false,
        $allowDiscountId = null
    )
    {
        $setting = Setting::first();

        $discount_percentage = 0;
        $discounted_price = 0;
        $product_price = 0;
        $discount_id = null;
        $club_points_used = 0;
        $expired_discount_info = null;

        if (!empty($discount_code)) {
            try {
                $discount = Discount::findByCode($discount_code);

                if (!$discount) {
                    throw new \Exception('کد تخفیف معتبر نیست');
                }

                if ($discount->scope === 'next_purchase') {
                    $this->validateNextPurchaseDiscount(
                        $discount,
                        $customer_id,
                        $reserve_number,
                        $totalPrice,
                        $allowDiscountId
                    );
                }

                if ($discount->profit_manager_ids) {
                    $applicableTotal = $this->getApplicableTotalPrice($orderDetails, $discount->profit_manager_ids);

                    if ($applicableTotal == 0) {
                        throw new \Exception('هیچ کدام از محصولات سفارش مشمول این تخفیف نمی‌باشند. این تخفیف فقط برای محصولات مرکز درآمد مشخصی قابل استفاده است');
                    }
                } else {
                    $applicableTotal = $totalPrice;
                }

                if ($discount->scope === 'next_purchase') {
                    if ($discount->discount_type === 'fixed') {
                        $discounted_price = $discount->discount_value;
                        $discounted_price = min($discounted_price, $applicableTotal);
                        $discount_percentage = ($applicableTotal > 0) ? ($discounted_price / $applicableTotal) * 100 : 0;
                    } else {
                        $discounted_price = (int) round(($applicableTotal * $discount->discount_value) / 100);
                        $discount_percentage = $discount->discount_value;
                    }
                    $discount_id = $discount->id;
                } else {
                    $dto = new ApplyDiscountDTO([
                        'code' => $discount_code,
                        'total_price' => $applicableTotal,
                        'profit_manager_id' => null, // Validation handled by getApplicableTotalPrice
                        'reserve_number' => $reserve_number,
                        'customer_id' => $customer_id,
                    ]);

                    $discountResult = $this->discountApplier->calculate($dto);
                    $discounted_price = intval(round($discountResult['discount_amount']));
                    $discount_percentage = ($applicableTotal > 0) ? ($discounted_price / $applicableTotal) * 100 : 0;
                    $discount_id = $discountResult['discount_id'];
                }
            } catch (\App\Exceptions\ExpiredDiscountException $e) {
                $expiredDiscount = $e->getDiscount();
                $expired_discount_info = [
                    'code' => $expiredDiscount->code,
                    'discount_type' => $expiredDiscount->discount_type,
                    'discount_value' => $expiredDiscount->discount_value,
                    'starts_at' => $expiredDiscount->starts_at,
                    'expires_at' => $expiredDiscount->expires_at,
                ];
            }
        }
        elseif ($use_club_points) {
            $clubPointsResult = $this->calculateClubPointsDiscount($reserve_number, $customer_id, $totalPrice);
            $discounted_price = $clubPointsResult['discount_amount'];
            $club_points_used = $clubPointsResult['points_used'];
            $club_points_remaining = $clubPointsResult['remaining_points'];
            $discount_percentage = ($totalPrice > 0) ? ($discounted_price / $totalPrice) * 100 : 0;
        }
        elseif (!empty($discount_type) && !empty($discount_value)) {
            $discountResult = $this->discountApplier->applyFinalize(
                $discount_type,
                $discount_value,
                $totalPrice
            );
            $discounted_price = intval(round($discountResult['discount_amount']));
            $discount_percentage = ($discount_type === 'percentage')
                ? $discount_value
                : ($discounted_price / $totalPrice) * 100;
        }

        $total_price_after_discount = 0;
        $item_discounts = [];

        foreach ($orderDetails as $order) {
            $food = Food::with('profitManager')->findOrFail($order['food_id']);
            $item_price = intval(round($food->price * $order['quantity']));
            $product_price += $item_price;

            $foodProfitManagerId = $food->profitManager->id ?? null;
            $shouldApplyDiscount = $this->shouldApplyDiscountToFood($discount_code, $foodProfitManagerId);

            if ($shouldApplyDiscount && $discounted_price > 0) {
                $discountProfitManagerIds = $discount_code ? Discount::findByCode($discount_code)->profit_manager_ids : null;
                $applicableTotal = $this->getApplicableTotalPrice($orderDetails, $discountProfitManagerIds);
                $item_discount = ($applicableTotal > 0) ? intval(round(($item_price / $applicableTotal) * $discounted_price)) : 0;
            } else {
                $item_discount = 0;
            }

            $item_discounts[] = $item_discount;
            $total_price_after_discount += $item_price - $item_discount;
        }

        $sum_discounts = array_sum($item_discounts);
        if ($sum_discounts != $discounted_price && $discounted_price > 0) {
            $diff = $discounted_price - $sum_discounts;

            foreach ($item_discounts as $index => $itemDiscount) {
                if ($itemDiscount > 0) {
                    $item_discounts[$index] += $diff;
                    break;
                }
            }

            $total_price_after_discount = 0;
            foreach ($orderDetails as $index => $order) {
                $food = Food::findOrFail($order['food_id']);
                $item_price = intval(round($food->price * $order['quantity']));
                $total_price_after_discount += $item_price - $item_discounts[$index];
            }
        }

        $serviceFee = 0;
        if ($rate_service) {
            $serviceFee = intval(round($total_price_after_discount * $setting->rate_service / 100));
        }

        $totalAfterServiceFee = $total_price_after_discount + $serviceFee;
        $taxAmount = intval(round($totalAfterServiceFee * $setting->tax / 100));

        $finalTotal = $total_price_after_discount + $serviceFee + $taxAmount;

        return [
            'discounted_price' => $discounted_price,
            'price' => $total_price_after_discount,
            'total_price' => $finalTotal,
            'tax_amount' => $taxAmount,
            'service_fee' => $serviceFee,
            'product_price' => $product_price,
            'discount_percentage' => $discount_percentage,
            'discount' => $discount_id,
            'club_points_used' => $club_points_used,
            'club_points_remaining' => $club_points_remaining ?? null,
            'expired_discount_info' => $expired_discount_info,
        ];
    }

    private function validateNextPurchaseDiscount($discount, $customer_id, $reserve_number, $totalPrice, $allowDiscountId = null)
    {
        if (!$discount->is_active) {
            throw new \Exception('کد تخفیف فعال نیست');
        }

        if ($discount->isExpired()) {
            throw new \App\Exceptions\ExpiredDiscountException('کد تخفیف منقضی شده است', 0, null, $discount);
        }

        if (!$discount->isValid()) {
            throw new \Exception('کد تخفیف معتبر نیست');
        }

        if ($discount->customer_id && $discount->customer_id != $customer_id) {
            throw new \Exception('این کد تخفیف متعلق به شما نیست');
        }

        if ($discount->reserve_number && $discount->reserve_number != $reserve_number) {
            throw new \Exception('این کد تخفیف متعلق به این رزرو نیست');
        }

        if ($totalPrice < $discount->minimum_price) {
            throw new \Exception('حداقل مبلغ سفارش برای استفاده از این تخفیف ' . number_format($discount->minimum_price) . ' ریال است');
        }

        // Already reserved on this pending order (usage was incremented at create)
        if ($allowDiscountId && (int) $allowDiscountId === (int) $discount->id) {
            return;
        }

        if ($discount->usage_limit && $discount->usage_count >= $discount->usage_limit) {
            throw new \Exception('ظرفیت استفاده از این تخفیف تکمیل شده است');
        }
    }

    private function calculateClubPointsDiscount($reserve_number, $customer_id, $totalPrice)
    {
        $clubSetting = \App\Models\ClubSetting::getActive();

        if (!$clubSetting) {
            throw new \Exception('تنظیمات باشگاه مشتریان یافت نشد. لطفا ابتدا تنظیمات را انجام دهید');
        }

        $userPoints = 0;

        if ($reserve_number) {
            $guestUser = \App\Models\GuestUser::where('Reserve', $reserve_number)->first();
            if (!$guestUser) {
                throw new \Exception('مشتری مقیم با این شماره رزرو یافت نشد');
            }
            $userPoints = $guestUser->total_points;
        } elseif ($customer_id) {
            $customer = \App\Models\Customer::find($customer_id);
            if (!$customer) {
                throw new \Exception('مشتری یافت نشد');
            }
            $userPoints = $customer->total_points;
        }

        if ($userPoints == 0) {
            throw new \Exception('شما امتیاز کافی برای استفاده ندارید');
        }

        if (!$clubSetting->canUsePoints($userPoints)) {
            throw new \Exception('حداقل ' . $clubSetting->getMinimumPointsForDiscount() . ' امتیاز برای استفاده از تخفیف لازم است');
        }

        $maxPossibleDiscount = $userPoints * $clubSetting->discount_amount_per_point;

        $actualDiscount = min($maxPossibleDiscount, $totalPrice);

        $pointsToUse = $actualDiscount / $clubSetting->discount_amount_per_point;

        $remainingPoints = $userPoints - $pointsToUse;

        return [
            'discount_amount' => intval($actualDiscount),
            'points_used' => $pointsToUse,
            'remaining_points' => $remainingPoints,
            'total_available_discount' => intval($maxPossibleDiscount)
        ];
    }

    private function getApplicableTotalPrice($orderDetails, $discountProfitManagerIds)
    {
        if (empty($discountProfitManagerIds)) {
            $total = 0;
            foreach ($orderDetails as $order) {
                $food = Food::findOrFail($order['food_id']);
                $total += intval(round($food->price * $order['quantity']));
            }
            return $total;
        }

        $applicableTotal = 0;
        foreach ($orderDetails as $order) {
            $food = Food::with('profitManager')->findOrFail($order['food_id']);
            $foodProfitManagerId = $food->profitManager->id ?? null;

            if (in_array($foodProfitManagerId, $discountProfitManagerIds)) {
                $applicableTotal += intval(round($food->price * $order['quantity']));
            }
        }

        return $applicableTotal;
    }

    private function shouldApplyDiscountToFood($discount_code, $foodProfitManagerId)
    {
        if (!$discount_code) {
            return true;
        }

        $discount = Discount::findByCode($discount_code);

        if (!$discount || empty($discount->profit_manager_ids)) {
            return true;
        }

        return in_array($foodProfitManagerId, $discount->profit_manager_ids);
    }

}
