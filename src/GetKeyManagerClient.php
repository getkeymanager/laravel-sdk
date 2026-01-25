<?php

declare(strict_types=1);

namespace GetKeyManager\Laravel;

use GetKeyManager\SDK\LicenseClient;
use GetKeyManager\Laravel\Core\LicenseState;
use GetKeyManager\Laravel\Core\EntitlementState;
use GetKeyManager\Laravel\Core\StateResolver;
use GetKeyManager\Laravel\Core\StateStore;
use GetKeyManager\Laravel\Core\SignatureVerifier;
use GetKeyManager\Laravel\Core\ApiResponseCode;
use GetKeyManager\Laravel\Core\LicenseException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

/**
 * Laravel-specific wrapper for License Manager SDK
 * 
 * Provides Laravel-friendly interface with logging, caching,
 * exception handling, and hardened license state management.
 * 
 * Version 2.0 - Hardened with LicenseState integration
 */
class GetKeyManagerClient
{
    private LicenseClient $client;
    private array $config;
    private StateResolver $stateResolver;
    private StateStore $stateStore;

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
        
        // Initialize hardening components
        $verifier = $config['verify_signatures'] && !empty($config['public_key'])
            ? new SignatureVerifier($config['public_key'])
            : null;
            
        $this->stateResolver = new StateResolver(
            $verifier,
            $config['environment'] ?? null,
            $config['product_id'] ?? null
        );
        
        $this->stateStore = new StateStore(
            $verifier,
            $config['state_cache_ttl'] ?? 3600 // Default 1 hour
        );
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
     * Resolve license state from validation (Hardened API)
     * 
     * This is the recommended way to validate licenses. It returns a LicenseState
     * object that provides multiple enforcement layers and handles grace periods.
     *
     * @param string $licenseKey License key to validate
     * @param array $options Validation options
     * @return LicenseState License state object
     * @throws LicenseException On validation errors
     */
    public function resolveLicenseState(string $licenseKey, array $options = []): LicenseState
    {
        $stateKey = $this->getStateKey($licenseKey);
        
        // Try to get cached state first
        if ($this->config['cache_enabled'] ?? false) {
            try {
                $cachedEntitlementState = $this->stateStore->get($stateKey);
                if ($cachedEntitlementState !== null) {
                    return new LicenseState($cachedEntitlementState, $licenseKey);
                }
            } catch (\Exception $e) {
                // Cached state invalid - continue to revalidate
            }
        }
        
        // Perform API validation
        try {
            $response = $this->client->validateLicense($licenseKey, $options);
            
            // Resolve state from response
            $licenseState = $this->stateResolver->resolveFromValidation($response, $licenseKey);
            
            // Store EntitlementState in cache
            if ($this->config['cache_enabled'] ?? false) {
                $this->stateStore->set($stateKey, $licenseState->getEntitlementState());
            }
            
            if ($this->isLoggingEnabled()) {
                Log::channel($this->getLogChannel())->info('License state resolved', [
                    'license_key' => substr($licenseKey, 0, 8) . '...',
                    'state' => $licenseState->getState(),
                    'is_valid' => $licenseState->isValid(),
                ]);
            }
            
            return $licenseState;
        } catch (Exception $e) {
            if ($this->isLoggingEnabled()) {
                Log::channel($this->getLogChannel())->error('License state resolution failed', [
                    'license_key' => substr($licenseKey, 0, 8) . '...',
                    'error' => $e->getMessage(),
                ]);
            }
            
            throw $e;
        }
    }

    /**
     * Get cached license state without API call
     * 
     * Returns null if no cached state exists or if cache has expired.
     *
     * @param string $licenseKey License key
     * @return LicenseState|null Cached license state or null
     */
    public function getLicenseState(string $licenseKey): ?LicenseState
    {
        $stateKey = $this->getStateKey($licenseKey);
        
        try {
            $cachedEntitlementState = $this->stateStore->get($stateKey);
            if ($cachedEntitlementState !== null) {
                return new LicenseState($cachedEntitlementState, $licenseKey);
            }
        } catch (\Exception $e) {
            // Ignore exceptions for cached reads
        }
        
        return null;
    }

    /**
     * Check if a specific feature is allowed for a license
     * 
     * This method uses the EntitlementState capabilities to check feature access.
     *
     * @param string $licenseKey License key
     * @param string $featureName Feature name to check
     * @param array $options Additional options
     * @return bool True if feature is allowed
     * @throws LicenseException On errors
     */
    public function isFeatureAllowed(string $licenseKey, string $featureName, array $options = []): bool
    {
        $state = $this->resolveLicenseState($licenseKey, $options);
        return $state->allows($featureName);
    }

    /**
     * Clear cached state for a license key
     *
     * @param string $licenseKey License key
     * @return void
     */
    public function clearLicenseState(string $licenseKey): void
    {
        $stateKey = $this->getStateKey($licenseKey);
        $this->stateStore->remove($stateKey);
    }

    /**
     * Clear all cached license states
     *
     * @return void
     */
    public function clearAllLicenseStates(): void
    {
        $this->stateStore->clear();
    }
    
    /**
     * Get state cache key for a license
     *
     * @param string $licenseKey License key
     * @return string Cache key
     */
    private function getStateKey(string $licenseKey): string
    {
        return $this->stateStore->getValidationKey($licenseKey);
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
    
    /**
     * Get state cache TTL in seconds
     *
     * @return int
     */
    private function getStateCacheTtl(): int
    {
        return $this->config['state_cache_ttl'] ?? 3600; // Default 1 hour
    }
}
