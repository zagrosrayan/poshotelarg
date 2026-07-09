<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResidentCustomerPoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'points',
        'price_purchased',
        'reserve_number',
    ];


    public function resident()
    {
        return $this->belongsTo(GuestUser::class, 'reserve_number', 'Reserve');
    }

    public static function getTotalPointsForReserve($reserve_number)
    {
        return (int) self::where('reserve_number', $reserve_number)->sum('points');
    }
}