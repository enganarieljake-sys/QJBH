<?php

namespace App\Providers;

use App\Support\StaffAccess;
use Illuminate\Support\Facades\Blade;
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
        // @staffcan('admin.bills.generate') ... @endstaffcan hides buttons a caretaker or panday can't use.
        Blade::if('staffcan', fn (string $routeName) => StaffAccess::allows(auth()->user(), $routeName));
    }
}
