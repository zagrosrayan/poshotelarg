<?php

namespace App\Providers;

use App\Repository\Discount\Contracts\DiscountApplierInterface;
use App\Repository\Discount\Contracts\DiscountCreatorInterface;
use App\Repository\Discount\Services\DiscountApplierService;
use App\Repository\Discount\Services\DiscountCreatorService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {

        $this->app->bind(DiscountCreatorInterface::class, DiscountCreatorService::class);
        $this->app->bind(DiscountApplierInterface::class, DiscountApplierService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
