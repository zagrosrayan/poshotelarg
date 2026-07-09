<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'user_id',
        'status',
        'reserve_number',
        'desc_number',
        'description',
        'payment_method',
        'price',
        'invoice_number',
        'total_price',
        'tax',
        'quantity',
        'discount_id',
        'food_id',
        'club_points_used',
        'order_date',
        'rate_service',
        'service_type',
        'serial_number',
        'discounted_price',
        'parent_id',
        'room_number',
        'next_purchase_discount_id',
        'expired_discount_info',
    ];
    protected $appends = ['product_price'];
    protected $casts = [
        'expired_discount_info' => 'array',
        'customer_id' => 'integer',
        // 'desc_number' => 'integer',  // این رو پاک کن
        'discount_id' => 'integer',
        'club_points_used' => 'integer',
        'is_special' => 'boolean',
    ];
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function getProductPriceAttribute()
    {
        // اگر product_price در attributes تنظیم شده باشد، همان را برگردان
        if (isset($this->attributes['product_price'])) {
            return $this->attributes['product_price'];
        }

        // در غیر این صورت، از children محاسبه کن
        return $this->children->sum(function ($child) {
            return $child->food ? intval(round($child->food->price * $child->quantity)) : 0;
        });
    }

    public function nextPurchaseDiscount()
    {
        return $this->belongsTo(NextPurchaseDiscount::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function status()
    {
        return $this->belongsTo(Type::class, 'status');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(Type::class, 'payment_method');
    }

    public function discount()
    {
        return $this->belongsTo(Discount::class);
    }

    public function food()
    {
        return $this->belongsTo(Food::class);
    }

    public function parent()
    {
        return $this->belongsTo(Order::class, 'parent_id');
    }
    public function reserve()
    {
        return $this->belongsTo(GuestUser::class, 'reserve_number','Reserve');
    }

    public function children()
    {
        return $this->hasMany(Order::class, 'parent_id');
    }

}
