<?php

namespace App\Models;

use App\Http\Service\TypeSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Morilog\Jalali\Jalalian;

class Customer extends Model
{
    use HasFactory;
    protected $guarded = [
        'id'
    ];

    protected $appends = [
        'total_points',
        'total_purchased',
        'pending_order_count',
        'pending_order_total',
        'complete_order_count',
        'complete_order_total',
        'last_order_date',
        'club_points',
        'next_purchase_discount_code',
        'next_purchase_discount_expires_at',
        'next_purchase_discount_amount',
    ];

    /** @var Discount|null|false */
    protected $cachedNextPurchaseDiscount = false;

    public function orders()
    {
        $type = Type::query()->where('slug', TypeSlug::ORDER_STATUS_COMPLETE)->first();
        return $this->hasMany(Order::class)->whereNull('parent_id')->where('status', $type->id);
    }

    /**
     * Unused next-purchase discount for this guest (includes expired, for UI warning).
     */
    protected function resolveNextPurchaseDiscount(): ?Discount
    {
        if ($this->cachedNextPurchaseDiscount !== false) {
            return $this->cachedNextPurchaseDiscount;
        }

        $this->cachedNextPurchaseDiscount = Discount::query()
            ->where('scope', 'next_purchase')
            ->where('customer_id', $this->id)
            ->where('is_active', true)
            ->whereColumn('usage_count', '<', 'usage_limit')
            ->latest('id')
            ->first();

        return $this->cachedNextPurchaseDiscount;
    }

    public function getClubPointsAttribute()
    {
        return $this->total_points;
    }

    public function getNextPurchaseDiscountCodeAttribute(): ?string
    {
        return $this->resolveNextPurchaseDiscount()?->code;
    }

    public function getNextPurchaseDiscountExpiresAtAttribute(): ?string
    {
        $expiresAt = $this->resolveNextPurchaseDiscount()?->expires_at;
        return $expiresAt ? $expiresAt->toIso8601String() : null;
    }

    public function getNextPurchaseDiscountAmountAttribute()
    {
        return $this->resolveNextPurchaseDiscount()?->discount_value;
    }

    public function allOrders()
    {
        return $this->hasMany(Order::class)->whereNull('parent_id');
    }

    public function pendingOrders()
    {
        $type = Type::query()->where('slug', TypeSlug::ORDER_STATUS_PENDING)->first();
        return $this->hasMany(Order::class)->whereNull('parent_id')->where('status', $type->id);
    }

    public function getTotalPointsAttribute()
    {
        if ($this->points !== null) {
            return $this->points;
        }

        $clubSetting = ClubSetting::getActive();
        if (!$clubSetting) {
            return 0;
        }

        $totalPurchased = $this->getCompleteOrderTotalAttribute();
        $pointsUsed = $this->orders()->sum('club_points_used') ?? 0;
        $earnedPoints = $clubSetting->calculateEarnedPoints($totalPurchased);

        return max(0, $earnedPoints - $pointsUsed);
    }

    public function getTotalPurchasedAttribute()
    {
        if ($this->price_purchased !== null) {
            return $this->price_purchased;
        }

        return $this->orders()->sum('total_price') ?? 0;
    }

    public function getPendingOrderCountAttribute()
    {
        return $this->pendingOrders()->count();
    }

    public function getPendingOrderTotalAttribute()
    {
        return $this->pendingOrders()->sum('total_price') ?? 0;
    }

    public function getCompleteOrderCountAttribute()
    {
        return $this->orders()->count();
    }

    public function getCompleteOrderTotalAttribute()
    {
        return $this->orders()->sum('total_price') ?? 0;
    }

    public function getLastOrderDateAttribute()
    {
        $lastOrder = $this->orders()->latest('created_at')->first();
        if (!$lastOrder) {
            return null;
        }
        return Jalalian::fromCarbon($lastOrder->created_at)->format('Y/m/d');
    }
}