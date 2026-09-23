<?php

namespace App\Providers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Shipping;
use App\Policies\CustomerPolicy;
use App\Policies\OrderPolicy;
use App\Policies\ShippingPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Shipping::class, ShippingPolicy::class);
    }
}
