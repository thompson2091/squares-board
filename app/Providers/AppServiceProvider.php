<?php

namespace App\Providers;

use App\Models\Square;
use App\Observers\SquareObserver;
use Illuminate\Support\Facades\Schema;
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
        // Fix for older MySQL versions with utf8mb4
        Schema::defaultStringLength(191);

        // Propagates season roster changes onto the weekly boards.
        Square::observe(SquareObserver::class);
    }
}
