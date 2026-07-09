<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\ClubSetting;
use App\Models\GuestUser;
use App\Models\ResidentCustomerPoint;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpgradeCustomersPoints implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $clubSetting = ClubSetting::latest()->first();

        if (!$clubSetting) {
            return;
        }

        $this->upgradeCustomers($clubSetting);
        $this->upgradeGuestUsers($clubSetting);
    }

    private function upgradeCustomers(ClubSetting $clubSetting): void
    {
        Customer::query()->chunk(100, function ($customers) use ($clubSetting) {
            foreach ($customers as $customer) {
                $totalPurchased = $customer->orders()->sum('total_price') ?? 0;
                $pointsUsed = $customer->orders()->sum('club_points_used') ?? 0;

                $earnedPoints = $clubSetting->calculateEarnedPoints($totalPurchased);
                $finalPoints = max(0, $earnedPoints - $pointsUsed);

                $customer->update([
                    'points' => $finalPoints,
                    'price_purchased' => $totalPurchased,
                ]);
            }
        });
    }

    private function upgradeGuestUsers(ClubSetting $clubSetting): void
    {
        GuestUser::query()->chunk(100, function ($guests) use ($clubSetting) {
            foreach ($guests as $guest) {
                ResidentCustomerPoint::where('reserve_number', $guest->Reserve)->delete();

                $totalPurchased = $guest->orders()->sum('total_price') ?? 0;
                $pointsUsed = $guest->orders()->sum('club_points_used') ?? 0;

                $earnedPoints = $clubSetting->calculateEarnedPoints($totalPurchased);
                $finalPoints = max(0, $earnedPoints - $pointsUsed);

                if ($finalPoints > 0 || $totalPurchased > 0) {
                    ResidentCustomerPoint::create([
                        'reserve_number' => $guest->Reserve,
                        'points' => $finalPoints,
                        'price_purchased' => $totalPurchased,
                    ]);
                }
            }
        });
    }
}