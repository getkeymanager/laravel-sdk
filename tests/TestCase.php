<?php

declare(strict_types=1);

namespace GetKeyManager\Laravel\Tests;

use GetKeyManager\Laravel\GetKeyManagerClient;
use GetKeyManager\Laravel\GetKeyManagerServiceProvider;
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
            GetKeyManagerServiceProvider::class,
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
        $app['config']->set('getkeymanager.api_key', 'test-api-key');
        $app['config']->set('getkeymanager.base_url', 'https://api.test.com');
        $app['config']->set('getkeymanager.environment', 'testing');
        $app['config']->set('getkeymanager.verify_signatures', false);
    }
}
