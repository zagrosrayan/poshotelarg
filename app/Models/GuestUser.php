<?php

namespace App\Models;

use App\Http\Service\TypeSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuestUser extends Model
{
    use HasFactory;

    protected $table = 'InhouseList';
    protected $guarded = [];

    protected $appends = ['total_points','total_purchased'];

    public function orders()
    {
        $type = Type::query()->where('slug', TypeSlug::ORDER_STATUS_COMPLETE)->first();
        return $this->hasMany(Order::class, 'reserve_number', 'Reserve')
            ->whereNull('parent_id')
            ->where('status', $type->id);
    }

    public function getTotalPointsAttribute()
    {
        return ResidentCustomerPoint::getTotalPointsForReserve($this->Reserve);
    }
    public function getTotalPurchasedAttribute()
    {
        return $this->orders()->sum('total_price') ?? 0;
    }

}