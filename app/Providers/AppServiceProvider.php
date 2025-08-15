<?php

namespace App\Providers;

use Alluvamz\PayChanguMobile\PayChanguIntegration;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            PayChanguIntegration::class,
            fn () => new PayChanguIntegration(Config::string('services.paychangu.secret'))
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::unguard();

    }
}
