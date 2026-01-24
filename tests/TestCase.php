<?php

declare(strict_types=1);

namespace LicenseManager\Laravel\Tests;

use LicenseManager\Laravel\LicenseManagerClient;
use LicenseManager\Laravel\LicenseManagerServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

/**
 * Base test case for Laravel SDK tests
 */
class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Additional setup
    }

    /**
     * Get package providers
     *
     * @param \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageProviders($app): array
    {
        return [
            LicenseManagerServiceProvider::class,
        ];
    }

    /**
     * Define environment setup
     *
     * @param \Illuminate\Foundation\Application $app
     * @return void
     */
    protected function getEnvironmentSetUp($app): void
    {
        // Set up test configuration
        $app['config']->set('licensemanager.api_key', 'test-api-key');
        $app['config']->set('licensemanager.base_url', 'https://api.test.com');
        $app['config']->set('licensemanager.environment', 'testing');
        $app['config']->set('licensemanager.verify_signatures', false);
    }
}
