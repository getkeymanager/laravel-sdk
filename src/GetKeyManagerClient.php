<?php

declare(strict_types=1);

namespace GetKeyManager\Laravel;

use GetKeyManager\SDK\LicenseClient;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Laravel-specific wrapper for License Manager SDK
 * 
 * Provides Laravel-friendly interface with logging, caching,
 * and exception handling.
 */
class GetKeyManagerClient
{
    private LicenseClient $client;
    private array $config;

    /**
     * Initialize the client
     *
     * @param array $config Configuration array from Laravel config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        
        // Initialize the base SDK client
        $this->client = new LicenseClient([
            'apiKey' => $config['api_key'],
            'baseUrl' => $config['base_url'],
            'environment' => $config['environment'] ?? null,
            'verifySignatures' => $config['verify_signatures'],
            'publicKey' => $config['public_key'] ?? null,
            'timeout' => $config['timeout'],
            'cacheEnabled' => $config['cache_enabled'],
            'cacheTtl' => $config['cache_ttl'],
            'retryAttempts' => $config['retry_attempts'],
            'retryDelay' => $config['retry_delay'],
        ]);
    }

    /**
     * Get the underlying SDK client
     *
     * @return LicenseClient
     */
    public function getClient(): LicenseClient
    {
        return $this->client;
    }

    /**
     * Validate a license key
     *
     * @param string $licenseKey
     * @param array $options
     * @return array
     * @throws Exception
     */
    public function validateLicense(string $licenseKey, array $options = []): array
    {
        return $this->executeWithLogging('validateLicense', function () use ($licenseKey, $options) {
            return $this->client->validateLicense($licenseKey, $options);
        });
    }

    /**
     * Activate a license
     *
     * @param string $licenseKey
     * @param array $options
     * @return array
     * @throws Exception
     */
    public function activateLicense(string $licenseKey, array $options = []): array
    {
        return $this->executeWithLogging('activateLicense', function () use ($licenseKey, $options) {
            return $this->client->activateLicense($licenseKey, $options);
        });
    }

    /**
     * Deactivate a license
     *
     * @param string $licenseKey
     * @param array $options
     * @return array
     * @throws Exception
     */
    public function deactivateLicense(string $licenseKey, array $options = []): array
    {
        return $this->executeWithLogging('deactivateLicense', function () use ($licenseKey, $options) {
            return $this->client->deactivateLicense($licenseKey, $options);
        });
    }

    /**
     * Check if a feature is enabled
     *
     * @param string $licenseKey
     * @param string $featureName
     * @return array
     * @throws Exception
     */
    public function checkFeature(string $licenseKey, string $featureName): array
    {
        return $this->executeWithLogging('checkFeature', function () use ($licenseKey, $featureName) {
            return $this->client->checkFeature($licenseKey, $featureName);
        });
    }

    /**
     * Validate offline license
     *
     * @param string|array $offlineLicenseData
     * @param array $options
     * @return array
     * @throws Exception
     */
    public function validateOfflineLicense($offlineLicenseData, array $options = []): array
    {
        return $this->executeWithLogging('validateOfflineLicense', function () use ($offlineLicenseData, $options) {
            return $this->client->validateOfflineLicense($offlineLicenseData, $options);
        });
    }

    /**
     * Get license details
     *
     * @param string $licenseKey
     * @return array
     * @throws Exception
     */
    public function getLicenseDetails(string $licenseKey): array
    {
        return $this->executeWithLogging('getLicenseDetails', function () use ($licenseKey) {
            return $this->client->getLicenseDetails($licenseKey);
        });
    }

    /**
     * Get license activations
     *
     * @param string $licenseKey
     * @return array
     * @throws Exception
     */
    public function getLicenseActivations(string $licenseKey): array
    {
        return $this->executeWithLogging('getLicenseActivations', function () use ($licenseKey) {
            return $this->client->getLicenseActivations($licenseKey);
        });
    }

    /**
     * Generate hardware ID
     *
     * @return string
     */
    public function generateHardwareId(): string
    {
        return $this->client->generateHardwareId();
    }

    /**
     * Generate UUID
     *
     * @return string
     */
    public function generateUuid(): string
    {
        return $this->client->generateUuid();
    }

    /**
     * Proxy all other method calls to the base client
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        return $this->executeWithLogging($method, function () use ($method, $parameters) {
            return $this->client->$method(...$parameters);
        });
    }

    /**
     * Execute a method with logging support
     *
     * @param string $method
     * @param callable $callback
     * @return mixed
     * @throws Exception
     */
    private function executeWithLogging(string $method, callable $callback)
    {
        if (!$this->isLoggingEnabled()) {
            return $callback();
        }

        try {
            $result = $callback();
            
            Log::channel($this->getLogChannel())->info("License Manager: {$method} succeeded", [
                'method' => $method,
                'success' => $result['success'] ?? true,
            ]);

            return $result;
        } catch (Exception $e) {
            Log::channel($this->getLogChannel())->error("License Manager: {$method} failed", [
                'method' => $method,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            throw $e;
        }
    }

    /**
     * Check if logging is enabled
     *
     * @return bool
     */
    private function isLoggingEnabled(): bool
    {
        return $this->config['logging']['enabled'] ?? false;
    }

    /**
     * Get log channel
     *
     * @return string
     */
    private function getLogChannel(): string
    {
        return $this->config['logging']['channel'] ?? 'stack';
    }
}
