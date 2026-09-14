<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
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
        // Behind Render's proxy the app itself is talked to over plain HTTP;
        // without this, asset() and route() URLs render as http:// and
        // browsers block/warn on the mixed content.
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }
    }
}
