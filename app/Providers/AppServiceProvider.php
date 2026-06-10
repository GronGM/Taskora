<?php

namespace App\Providers;

use App\Models\Service;
use App\Models\Order;
use App\Models\Task;
use App\Models\TaskOffer;
use App\Policies\OrderPolicy;
use App\Policies\ServicePolicy;
use App\Policies\TaskOfferPolicy;
use App\Policies\TaskPolicy;
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
        Gate::policy(Service::class, ServicePolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Task::class, TaskPolicy::class);
        Gate::policy(TaskOffer::class, TaskOfferPolicy::class);
    }
}
