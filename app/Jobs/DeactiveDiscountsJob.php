<?php

namespace App\Jobs;

use App\Models\Discount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeactiveDiscountsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $discounts = Discount::all();
        foreach ($discounts as $discount) {
            if ($discount->expires_at !== null && $discount->expires_at->isPast()) {
                $discount->update(['is_active' => false]);
            }
        }
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //
    }
}
