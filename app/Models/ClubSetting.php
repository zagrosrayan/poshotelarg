<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClubSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'points_per_purchase',
        'amount_per_point',
        'points_per_discount',
        'discount_amount_per_point',
    ];

    public function calculateEarnedPoints(float $purchaseAmount): int
    {
        if ($purchaseAmount <= 0 || $this->amount_per_point == 0) {
            return 0;
        }

        $units = floor($purchaseAmount / $this->amount_per_point);
        return (int) ($units * $this->points_per_purchase);
    }

    public function calculateDiscount(int $userPoints): array
    {
        if ($userPoints < $this->points_per_discount) {
            return [
                'discount_amount' => 0,
                'used_points' => 0,
                'remaining_points' => $userPoints,
                'can_use' => false,
            ];
        }

        $maxPossibleDiscount = $userPoints * $this->discount_amount_per_point;

        return [
            'discount_amount' => (int) $maxPossibleDiscount,
            'used_points' => 0,
            'remaining_points' => $userPoints,
            'can_use' => true,
        ];
    }

    public function calculateMaxDiscount(int $userPoints): int
    {
        $result = $this->calculateDiscount($userPoints);
        return $result['discount_amount'];
    }

    public function getMinimumPointsForDiscount(): int
    {
        return $this->points_per_discount;
    }

    public function canUsePoints(int $userPoints): bool
    {
        return $userPoints >= $this->points_per_discount;
    }

    public static function getActive()
    {
        return self::first();
    }
}