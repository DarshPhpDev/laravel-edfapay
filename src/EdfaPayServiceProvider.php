<?php

namespace DarshPhpDev\EdfaPay;

use Illuminate\Support\ServiceProvider;
use DarshPhpDev\EdfaPay\Services\EdfaPayClient;

class EdfaPayServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        // 1. Publish configuration file mapping
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/edfapay.php' => config_path('edfapay.php'),
            ], 'edfapay-config');
        }

        // 2. Load dynamic package route for the webhook if enabled
        $this->registerWebhookRoute();
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Automatically merge fallback configurations
        $this->mergeConfigFrom(__DIR__ . '/../config/edfapay.php', 'edfapay');

        // Bind core service instance as an IoC Singleton block
        $this->app->singleton('edfapay', function ($app) {
            return new EdfaPayClient($app['config']['edfapay']);
        });
    }

    /**
     * Dynamically register the EdfaPay webhook route based on config variables.
     */
    protected function registerWebhookRoute(): void
    {
        $config = $this->app['config']['edfapay.webhook'];

        if (!empty($config['enable_default_route'])) {
            $this->app['router']
                ->post($config['path'], [\DarshPhpDev\EdfaPay\Http\Controllers\EdfaPayWebhookController::class, 'handle'])
                ->middleware($config['middleware'])
                ->name('edfapay.webhook');
        }
    }
}