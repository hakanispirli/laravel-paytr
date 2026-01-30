<?php

namespace Hakanispirli\Paytr\Providers;

use Illuminate\Support\ServiceProvider;
use Hakanispirli\Paytr\Services\PaytrService;
use Hakanispirli\Paytr\Contracts\PaytrInterface;

class PaytrServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge package config
        $this->mergeConfigFrom(__DIR__ . '/../../config/paytr.php', 'paytr');

        // Bind interface to implementation
        $this->app->singleton(PaytrInterface::class, PaytrService::class);

        // Bind 'paytr' alias for Facade
        $this->app->singleton('paytr', function ($app) {
            return $app->make(PaytrInterface::class);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__ . '/../../config/paytr.php' => config_path('paytr.php'),
        ], 'paytr-config');

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
    }
}
