<?php

namespace App\Providers;

use App\Models\Dispute;
use App\Models\Order;
use App\Models\Service;
use App\Models\Task;
use App\Models\TaskOffer;
use App\Policies\DisputePolicy;
use App\Policies\OrderPolicy;
use App\Policies\ServicePolicy;
use App\Policies\TaskOfferPolicy;
use App\Policies\TaskPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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
        Gate::policy(Dispute::class, DisputePolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Task::class, TaskPolicy::class);
        Gate::policy(TaskOffer::class, TaskOfferPolicy::class);

        RateLimiter::for(
            'taskora-order-messages',
            fn (Request $request): Limit => Limit::perMinute(20)->by($this->rateLimitKey($request)),
        );

        RateLimiter::for(
            'taskora-order-files',
            fn (Request $request): Limit => Limit::perMinutes(10, 10)->by($this->rateLimitKey($request)),
        );

        RateLimiter::for(
            'taskora-create',
            fn (Request $request): Limit => Limit::perHour(10)->by($this->rateLimitKey($request)),
        );

        RateLimiter::for(
            'taskora-offers',
            fn (Request $request): Limit => Limit::perHour(30)->by($this->rateLimitKey($request)),
        );

        RateLimiter::for(
            'taskora-notifications',
            fn (Request $request): Limit => Limit::perMinute(60)->by($this->rateLimitKey($request)),
        );
    }

    private function rateLimitKey(Request $request): string
    {
        return (string) ($request->user()?->getAuthIdentifier() ?? $request->ip());
    }
}
