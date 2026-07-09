<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'discount_value',
        'minimum_price',
        'profit_manager_ids',
        'is_special',
        'customer_id',
        'reserve_number',
        'is_unlimited',
        'is_active',
        'scope',
        'usage_limit',
        'usage_count',
        'starts_at',
        'expires_at',
        'discount_type',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'is_unlimited' => 'boolean',
        'is_special' => 'boolean',
        'profit_manager_ids' => 'array',
    ];

    public function isGlobal()
    {
        return $this->scope == 'global';
    }

    public function isNormal()
    {
        return $this->scope == 'normal';
    }

    public function isInOrder()
    {
        return $this->scope == 'in_order';
    }

    public function isActive()
    {
        return $this->is_active;
    }
    public function reserve()
    {
        // Todo :: check this relation work .
        return $this->belongsTo(GuestUser::class, 'reserve_number', 'Reserve');
    }

    public function customer()
    {
        // Todo :: check this relation work .
        return $this->belongsTo(Customer::class);
    }

    public function status()
    {
        // Todo :: check this relation work .
        return $this->belongsTo(Type::class, 'status_id');
    }

    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isValid()
    {
        return $this->is_active && !$this->isExpired();
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'discount_id');
    }


    public static function isValidCode($code)
    {
        $discount = self::where('code', $code)->first();
        if ($discount && $discount->isValid()) {
            return true;
        }

        return false;
    }

    public static function findByCode($code)
    {
        return self::query()->where('code', $code)->first();
    }
}
