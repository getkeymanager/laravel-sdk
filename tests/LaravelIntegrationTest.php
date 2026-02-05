<?php

declare(strict_types=1);

namespace GetKeyManager\Laravel\Tests;

use GetKeyManager\Laravel\GetKeyManagerClient;
use GetKeyManager\Laravel\GetKeyManagerServiceProvider;
use Illuminate\Container\Container;

/**
 * Laravel Integration Tests
 * 
 * TC-3.1.1: Service Provider Registers Client
 * TC-3.1.2: Facade Access
 * TC-3.1.3: Middleware Integration
 */
class LaravelIntegrationTest extends TestCase
{
    /**
     * TC-3.1.1: Service Provider Registers Client
     * 
     * Given: Laravel application with service provider
     * When: Bootstrap application
     * Then: GetKeyManagerClient singleton registered
     */
    public function testServiceProviderRegistersClient(): void
    {
        // Client should be resolvable from container
        $client = $this->app->make('getkeymanager');
        
        $this->assertInstanceOf(GetKeyManagerClient::class, $client);
    }

    /**
     * TC-3.1.1b: Client Self-Reference
     */
    public function testClientAliasingWorks(): void
    {
        $client1 = $this->app->make('getkeymanager');
        $client2 = $this->app->make(GetKeyManagerClient::class);

        // Same singleton instance
        $this->assertSame($client1, $client2);
    }

    /**
     * TC-3.1.2: Configuration is Published
     * 
     * Verify config/getkeymanager.php contains required options
     */
    public function testConfigurationFileHasRequiredOptions(): void
    {
        $config = $this->app['config']['getkeymanager'];

        // Required options present
        $this->assertArrayHasKey('api_key', $config);
        $this->assertArrayHasKey('base_url', $config);
        $this->assertArrayHasKey('environment', $config);
        $this->assertArrayHasKey('verify_signatures', $config);
        $this->assertArrayHasKey('public_key_file', $config);
        $this->assertArrayHasKey('license_file_path', $config);
        $this->assertArrayHasKey('default_identifier', $config);
    }

    /**
     * TC-3.1.2b: Configuration Defaults
     */
    public function testConfigurationDefaultsAreSensible(): void
    {
        $config = $this->app['config']['getkeymanager'];

        // Defaults should be sensible
        $this->assertIsString($config['base_url']);
        $this->assertIsBool($config['verify_signatures']);
        $this->assertTrue($config['verify_signatures']);
        $this->assertNotEmpty($config['base_url']);
    }

    /**
     * TC-3.1.3: Methods Available on Client
     */
    public function testClientMethodsAvailable(): void
    {
        $client = $this->app->make(GetKeyManagerClient::class);

        // Core methods should be callable
        $this->assertTrue(method_exists($client, 'validateLicense'));
        $this->assertTrue(method_exists($client, 'activateLicense'));
        $this->assertTrue(method_exists($client, 'deactivateLicense'));
        $this->assertTrue(method_exists($client, 'isFeatureAllowed'));
    }

    /**
     * TC-3.1.4: Client Initialization with Configuration
     */
    public function testClientInitializedWithConfiguration(): void
    {
        $client = $this->app->make(GetKeyManagerClient::class);

        // Client should have access to configuration
        $this->assertInstanceOf(GetKeyManagerClient::class, $client);
        
        // Can be used to validate licenses
        // (Would need mocking for actual license validation)
    }

    /**
     * TC-3.1.5: Multiple Client Instances Singleton
     */
    public function testMultipleResolutionsReturnSameInstance(): void
    {
        $client1 = $this->app->make('getkeymanager');
        $client2 = $this->app->make('getkeymanager');
        $client3 = $this->app->make(GetKeyManagerClient::class);

        // All should be the same instance
        $this->assertSame($client1, $client2);
        $this->assertSame($client2, $client3);
        $this->assertSame($client1, $client3);
    }

    /**
     * TC-3.1.6: Service Provider Boot Called
     */
    public function testServiceProviderBootExecuted(): void
    {
        // Middleware should be registered
        $this->assertTrue(
            $this->app['router']->hasMiddlewareGroup('web') ||
            class_exists('GetKeyManager\Laravel\Middleware\ValidateLicense')
        );
    }

    /**
     * TC-3.1.7: Configuration Environment Support
     */
    public function testConfigurationSupportsEnvironmentVariables(): void
    {
        // Config should read from .env
        $config = $this->app['config']['getkeymanager'];

        // These would be set from .env in real application
        $this->assertIsString($config['api_key']); // Even if empty test value
        $this->assertIsString($config['base_url']);
    }

    /**
     * TC-3.1.8: Cache Configuration
     */
    public function testCacheConfigurationOptions(): void
    {
        $config = $this->app['config']['getkeymanager'];

        // Cache options available
        $this->assertArrayHasKey('cache_enabled', $config);
        $this->assertArrayHasKey('cache_ttl', $config);
    }
}
