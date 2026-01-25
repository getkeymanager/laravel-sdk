<?php

declare(strict_types=1);

namespace GetKeyManager\Laravel;

use Illuminate\Support\ServiceProvider;
use GetKeyManager\SDK\LicenseClient;

/**
 * License Manager Service Provider
 * 
 * Registers the License Manager SDK with Laravel's service container
 * and publishes configuration files.
 */
class GetKeyManagerServiceProvider extends ServiceProvider
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
            __DIR__.'/../config/getkeymanager.php',
            'getkeymanager'
        );

        // Register the SDK client as a singleton
        $this->app->singleton('getkeymanager', function ($app) {
            $config = $app['config']['getkeymanager'];

            return new GetKeyManagerClient($config);
        });

        // Register the client alias
        $this->app->alias('getkeymanager', GetKeyManagerClient::class);
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
                __DIR__.'/../config/getkeymanager.php' => config_path('getkeymanager.php'),
            ], 'getkeymanager-config');

            // Register commands
            $this->commands([
                Commands\LicenseValidateCommand::class,
                Commands\LicenseActivateCommand::class,
                Commands\LicenseDeactivateCommand::class,
                Commands\LicenseCheckStateCommand::class,
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
        return ['getkeymanager', GetKeyManagerClient::class];
    }
}
