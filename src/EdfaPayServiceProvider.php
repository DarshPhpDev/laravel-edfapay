<?php

namespace DarshPhpDev\EdfaPay;

use Illuminate\Support\ServiceProvider;
use DarshPhpDev\EdfaPay\Services\EdfaPayClient;
use DarshPhpDev\EdfaPay\Console\Commands\EdfaPayCheckCommand;

class EdfaPayServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/edfapay.php' => config_path('edfapay.php'),
            ], 'edfapay-config');

            $this->commands([EdfaPayCheckCommand::class]);
        }

        $this->registerWebhookRoute();
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/edfapay.php', 'edfapay');

        $this->app->singleton('edfapay', function ($app) {
            if (empty($app['config']['edfapay.api_key'])) {
                throw new \RuntimeException('EdfaPay API key is not configured. Set EDFAPAY_API_KEY in your .env file.');
            }

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