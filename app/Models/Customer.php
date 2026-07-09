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
        'last_order_date'
    ];

    public function orders()
    {
        $type = Type::query()->where('slug', TypeSlug::ORDER_STATUS_COMPLETE)->first();
        return $this->hasMany(Order::class)->whereNull('parent_id')->where('status', $type->id);
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