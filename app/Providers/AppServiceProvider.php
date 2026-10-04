<?php

namespace App\Providers;

use App\Models\Sermon;
use App\Observers\SermonObserver;
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
        Sermon::observe(SermonObserver::class);
    }
}
