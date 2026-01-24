<?php

declare(strict_types=1);

namespace LicenseManager\Laravel;

use Illuminate\Support\ServiceProvider;
use LicenseManager\SDK\LicenseClient;

/**
 * License Manager Service Provider
 * 
 * Registers the License Manager SDK with Laravel's service container
 * and publishes configuration files.
 */
class LicenseManagerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        // Merge package config with app config
        $this->mergeConfigFrom(
            __DIR__.'/../config/licensemanager.php',
            'licensemanager'
        );

        // Register the SDK client as a singleton
        $this->app->singleton('licensemanager', function ($app) {
            $config = $app['config']['licensemanager'];

            return new LicenseManagerClient($config);
        });

        // Register the client alias
        $this->app->alias('licensemanager', LicenseManagerClient::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Publish configuration file
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/licensemanager.php' => config_path('licensemanager.php'),
            ], 'licensemanager-config');

            // Register commands
            $this->commands([
                Commands\LicenseValidateCommand::class,
                Commands\LicenseActivateCommand::class,
                Commands\LicenseDeactivateCommand::class,
            ]);
        }

        // Register middleware
        $router = $this->app['router'];
        $router->aliasMiddleware('license.validate', Middleware\ValidateLicense::class);
        $router->aliasMiddleware('license.feature', Middleware\CheckFeature::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<string>
     */
    public function provides(): array
    {
        return ['licensemanager', LicenseManagerClient::class];
    }
}
