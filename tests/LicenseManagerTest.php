<?php

declare(strict_types=1);

namespace LicenseManager\Laravel\Tests;

use LicenseManager\Laravel\Facades\LicenseManager;
use LicenseManager\Laravel\LicenseManagerClient;

/**
 * License Manager Client Tests
 */
class LicenseManagerTest extends TestCase
{
    public function test_service_provider_registers_client()
    {
        $client = $this->app->make('getkeymanager');
        
        $this->assertInstanceOf(LicenseManagerClient::class, $client);
    }

    public function test_facade_resolves_correctly()
    {
        $this->assertInstanceOf(LicenseManagerClient::class, LicenseManager::getFacadeRoot());
    }

    public function test_config_is_loaded()
    {
        $this->assertEquals('test-api-key', config('getkeymanager.api_key'));
        $this->assertEquals('https://api.test.com', config('getkeymanager.base_url'));
        $this->assertEquals('testing', config('getkeymanager.environment'));
    }

    public function test_can_generate_hardware_id()
    {
        $hardwareId = LicenseManager::generateHardwareId();
        
        $this->assertIsString($hardwareId);
        $this->assertNotEmpty($hardwareId);
    }

    public function test_can_generate_uuid()
    {
        $uuid = LicenseManager::generateUuid();
        
        $this->assertIsString($uuid);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $uuid
        );
    }

    public function test_middleware_is_registered()
    {
        $router = $this->app['router'];
        
        $this->assertTrue($router->hasMiddlewareAlias('license.validate'));
        $this->assertTrue($router->hasMiddlewareAlias('license.feature'));
    }

    // Mock example for testing validation
    public function test_validate_license_returns_array()
    {
        // This is a mock test - in real tests, you would mock HTTP responses
        $client = $this->app->make(LicenseManagerClient::class);
        
        $this->assertInstanceOf(LicenseManagerClient::class, $client);
        
        // In real tests, mock the HTTP client:
        // $mockResponse = ['success' => true, 'data' => [...]]
        // Http::fake(['*' => Http::response($mockResponse)]);
        // $result = $client->validateLicense('TEST-KEY');
        // $this->assertTrue($result['success']);
    }
}
