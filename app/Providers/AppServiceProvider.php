<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use App\Services\DeviceClock;
use App\Services\ExpiryAlerts;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(DeviceClock::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.tailwind');
        foreach ([\App\Models\Product::class, \App\Models\StockIn::class, \App\Models\Sale::class,
            \App\Models\SoldItem::class, \App\Models\Category::class, \App\Models\Status::class,
            \App\Models\PaymentMethod::class, \App\Models\User::class] as $model) {
            $model::observe(\App\Observers\RefreshFilterCache::class);
        }
        View::composer('layouts.app', function ($view) {
            if (auth()->user()?->isAdmin()) {
                $view->with('expiryAlerts', app(ExpiryAlerts::class)->summary());
            }
        });
    }
}
