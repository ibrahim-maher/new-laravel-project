<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
        $this->app->singleton(
            Illuminate\Contracts\Foundation\MaintenanceMode::class,
            Illuminate\Foundation\FileBasedMaintenanceMode::class
        );
        
        $this->app->singleton('files', function () {
            return new \Illuminate\Filesystem\Filesystem;
        });
        
        // Keep any existing code here
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
