<?php

namespace Thorazine\Analytics;

use Illuminate\Support\ServiceProvider;
// use Spatie\Analytics\Exceptions\InvalidConfiguration;

class AnalyticsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/analytics.php' => config_path('analytics.php'),
        ]);
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/analytics.php', 'analytics');

        $this->app->bind(Analytics::class, function () {
            return new Analytics();
        });

        $this->app->alias(Analytics::class, 'laravel-analytics');
    }

    // protected function guardAgainstInvalidConfiguration(array $analyticsConfig = null)
    // {
    //     if (empty($analyticsConfig['view_id'])) {
    //         throw InvalidConfiguration::viewIdNotSpecified();
    //     }

    //     if (is_array($analyticsConfig['service_account_credentials_json'])) {
    //         return;
    //     }

    //     if (! file_exists($analyticsConfig['service_account_credentials_json'])) {
    //         throw InvalidConfiguration::credentialsJsonDoesNotExist($analyticsConfig['service_account_credentials_json']);
    //     }
    // }
}
