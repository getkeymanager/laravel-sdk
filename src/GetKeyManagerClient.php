<?php

declare(strict_types=1);

namespace GetKeyManager\Laravel;

use GetKeyManager\SDK\LicenseClient;
use GetKeyManager\SDK\Constants\ValidationType;
use GetKeyManager\SDK\Constants\IdentifierType;
use GetKeyManager\SDK\Constants\OptionKeys;
use GetKeyManager\SDK\Dto\ValidationResultDto;
use GetKeyManager\SDK\Dto\ActivationResultDto;
use GetKeyManager\SDK\Dto\SyncResultDto;
use GetKeyManager\SDK\Dto\LicenseDataDto;
use GetKeyManager\Laravel\Core\LicenseState;
use GetKeyManager\Laravel\Core\EntitlementState;
use GetKeyManager\Laravel\Core\StateResolver;
use GetKeyManager\Laravel\Core\StateStore;
use GetKeyManager\Laravel\Core\SignatureVerifier;
use GetKeyManager\Laravel\Core\ApiResponseCode;
use GetKeyManager\Laravel\Core\Exceptions\LicenseException;
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
        
        // Load public key from file if configured
        $publicKey = $this->loadPublicKey($config);
        
        // Initialize the base SDK client with all configuration options
        $this->client = new LicenseClient([
            'apiKey' => $config['api_key'],
            'baseUrl' => $config['base_url'],
            'environment' => $config['environment'] ?? null,
            'verifySignatures' => $config['verify_signatures'],
            'publicKey' => $publicKey,
            'productPublicKey' => $publicKey, // For offline .lic file validation
            'licenseFilePath' => $config['license_file_path'] ?? null,
            'defaultIdentifier' => $config['default_identifier'] ?? null,
            'timeout' => $config['timeout'],
            'cacheEnabled' => $config['cache_enabled'],
            'cacheTtl' => $config['cache_ttl'],
            'retryAttempts' => $config['retry_attempts'],
            'retryDelay' => $config['retry_delay'],
        ]);
        
        // Initialize hardening components
        $verifier = $config['verify_signatures'] && !empty($publicKey)
            ? new SignatureVerifier($publicKey)
            : null;
            
        $this->stateResolver = new StateResolver(
            $verifier,
            $config['environment'] ?? null,
            $config['product_id'] ?? null
        );
        
        $this->stateStore = new StateStore(
            $verifier,
            intval($config['state_cache_ttl']) ?? 3600 // Default 1 hour
        );
    }

    /**
     * Load public key from file or configuration
     *
     * @param array $config Configuration array
     * @return string|null Public key content or null
     * @throws LicenseException If public key file cannot be read
     */
    private function loadPublicKey(array $config): ?string
    {
        // Try to load from public_key_file first (preferred method)
        if (!empty($config['public_key_file'])) {
            $filePath = $this->resolvePath($config['public_key_file']);
            
            if (!file_exists($filePath)) {
                throw new LicenseException(
                    "Public key file not found: {$config['public_key_file']}",
                    LicenseException::ERROR_INVALID_PUBLIC_KEY
                );
            }
            
            $content = file_get_contents($filePath);
            if ($content === false) {
                throw new LicenseException(
                    "Cannot read public key file: {$config['public_key_file']}",
                    LicenseException::ERROR_INVALID_PUBLIC_KEY
                );
            }
            
            return trim($content);
        }
        
        // Fall back to public_key from config (deprecated)
        return !empty($config['public_key']) ? $config['public_key'] : null;
    }

    /**
     * Resolve file path with support for Laravel paths
     *
     * @param string $path File path (can use storage_path, config_path, base_path)
     * @return string Resolved absolute path
     */
    private function resolvePath(string $path): string
    {
        // Support Laravel path helpers in string format
        if (strpos($path, 'storage_path:') === 0) {
            return storage_path(substr($path, 13));
        }
        if (strpos($path, 'config_path:') === 0) {
            return config_path(substr($path, 12));
        }
        if (strpos($path, 'base_path:') === 0) {
            return base_path(substr($path, 10));
        }
        
        // If path starts with /, it's already absolute
        if (strpos($path, '/') === 0) {
            return $path;
        }
        
        // Relative paths are resolved from project root
        return base_path($path);
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
            $response = $this->normalizeApiResponse($this->client->validateLicense($licenseKey, $options));
            
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
     * Validate a license key with identifier support
     * 
     * Validates a license against the GetKeyManager API. Supports offline-first validation
     * by default when a cached .lic file is available.
     * 
     * ### Strategy
     * 1. **Offline-first (default):** Tries to parse and verify cached .lic file
     * 2. **API fallback:** If offline fails or force=true, calls the validation API
     * 3. **Fresh installs:** For new installations without a .lic file, use force=true
     * 
     * ### Parameters
     * - `$licenseKey` (string, required): Your license key (e.g., "LIC-2024-ABC123")
     * - `$identifier` (string, optional): Domain, HWID, or user identifier. Auto-generated if empty.
     * - `$publicKey` (string, optional): RSA public key for offline verification. Inherits from config.
     * - `$force` (bool, optional): ValidationType::FORCE_API to skip offline, OFFLINE_FIRST (default) for cache-first
     * - `$options` (array, optional): Additional options (cache_ttl, timeout, metadata, etc.)
     * 
     * ### Examples
     * ```php
     * // Basic validation with auto-generated identifier
     * $result = $client->validateLicense('LIC-2024-ABC123');
     * 
     * // Validation with specific domain
     * $result = $client->validateLicense('LIC-2024-ABC123', 'example.com');
     * 
     * // Force API call (useful for fresh installs)
     * $result = $client->validateLicense('LIC-2024-ABC123', 'example.com', null, true);
     * 
     * // With custom options
     * $result = $client->validateLicense('LIC-2024-ABC123', 'example.com', null, false, [
     *     'cache_ttl' => 3600,
     *     'timeout' => 10,
     *     'metadata' => ['app_version' => '1.0.0']
     * ]);
     * ```
     *
     * @param string $licenseKey License key to validate
     * @param string $identifier Domain, HWID, or identifier (auto-generated if empty)
     * @param string|null $publicKey RSA public key (inherits from config if null)
     * @param bool $force ValidationType::FORCE_API=true to force API, OFFLINE_FIRST=false (default) for cache-first
     * @param array $options Additional validation options
     * @return array Response array (backward compatible)
     * @throws Exception On validation errors
     */
    public function validateLicense(
        string $licenseKey, 
        string $identifier = '', 
        ?string $publicKey = null, 
        bool $force = false,
        array $options = []
    ): array {
        return $this->executeWithLogging('validateLicense', function () use ($licenseKey, $identifier, $publicKey, $force, $options) {
            // Auto-generate identifier if empty
            if (empty($identifier) && !empty($this->config['default_identifier'])) {
                $identifier = $this->config['default_identifier'];
            }
            if (empty($identifier)) {
                $identifier = $this->client->generateIdentifier(IdentifierType::AUTO);
            }
            
            // Resolve public key if not provided
            $publicKey = $publicKey ?? $this->config['public_key_file'] ?? null;
            
            // Delegate to SDK client
            $result = $this->client->validateLicense($licenseKey, $identifier, $publicKey, $force, $options);
            
            // Convert DTO to array for backward compatibility
            if ($result instanceof ValidationResultDto) {
                return $result->toArray();
            }
            
            return $result;
        });
    }

    /**
     * Activate a license with identifier support
     * 
     * Registers a new activation for a license on a specific domain or hardware.
     * Activations limit concurrent usage (e.g., 2 activations per license = 2 concurrent users).
     * 
     * ### Parameters
     * - `$licenseKey` (string, required): Your license key
     * - `$identifier` (string, optional): Domain or HWID for this activation. Auto-generated if empty.
     * - `$publicKey` (string, optional): RSA public key. Inherits from config if null.
     * - `$options` (array, optional): Activation options (OS, product_version, idempotency_key, etc.)
     * 
     * ### Key Options
     * - `idempotency_key`: Generate license .lic file on success (recommended)
     * - `os`: Operating system name
     * - `product_version`: Application version
     * - `hwid`: Hardware ID for this activation
     * - `domain`: Domain for this activation
     * 
     * ### Examples
     * ```php
     * // Basic activation
     * $result = $client->activateLicense('LIC-2024-ABC123', 'example.com');
     * 
     * // Activation with idempotency
     * $result = $client->activateLicense('LIC-2024-ABC123', 'example.com', null, [
     *     'idempotency_key' => 'user-request-uuid-here',
     *     'os' => 'Linux',
     *     'product_version' => '2.0.0'
     * ]);
     * 
     * // Desktop app activation with hardware ID
     * $result = $client->activateLicense('LIC-2024-ABC123', 'workstation-01', null, [
     *     'hwid' => '00-11-22-33-44-55',
     *     'os' => 'Windows 11'
     * ]);
     * ```
     * 
     * ### Error Handling
     * On activation limits exceeded:
     * - Response includes `error: 'activation_limit_exceeded'`
     * - Call deactivateLicense() to free a slot
     * - Then retry activation
     *
     * @param string $licenseKey License key to activate
     * @param string $identifier Domain or HWID for this activation (auto-generated if empty)
     * @param string|null $publicKey RSA public key (inherits from config if null)
     * @param array $options Activation options (idempotency_key, os, product_version, etc.)
     * @return array Response array (backward compatible)
     * @throws Exception On activation errors
     */
    public function activateLicense(
        string $licenseKey, 
        string $identifier = '', 
        ?string $publicKey = null, 
        array $options = []
    ): array {
        return $this->executeWithLogging('activateLicense', function () use ($licenseKey, $identifier, $publicKey, $options) {
            // Auto-generate identifier if empty
            if (empty($identifier) && !empty($this->config['default_identifier'])) {
                $identifier = $this->config['default_identifier'];
            }
            if (empty($identifier)) {
                $identifier = $this->client->generateIdentifier(IdentifierType::AUTO);
            }
            
            // Resolve public key if not provided
            $publicKey = $publicKey ?? $this->config['public_key_file'] ?? null;
            
            // Delegate to SDK client
            $result = $this->client->activateLicense($licenseKey, $identifier, $publicKey, $options);
            
            // Convert DTO to array for backward compatibility
            if ($result instanceof ActivationResultDto) {
                return $result->toArray();
            }
            
            return $result;
        });
    }

    /**
     * Check if a feature is enabled with identifier support
     * 
     * Verifies that a license has access to a specific feature. Returns false if license
     * is invalid, expired, missing the feature, or on any error (fail-secure).
     * 
     * ### Parameters
     * - `$licenseKey` (string, required): Your license key
     * - `$featureName` (string, required): Feature name to check
     * - `$identifier` (string, optional): Domain or HWID. Auto-generated if empty.
     * - `$publicKey` (string, optional): RSA public key. Inherits from config if null.
     * - `$force` (bool, optional): true to force API, false (default) for offline-first
     * - `$options` (array, optional): Additional options
     * 
     * ### Common Features (Examples)
     * Platform applies feature names from your product definition:
     * - `"api_access"` - Can use REST API
     * - `"export"` - Can export data
     * - `"analytics"` - Can view analytics dashboard
     * - `"users"` - Multi-user support
     * 
     * ### Safety Guarantees
     * Returns `false` on:
     * - License validation failure (invalid, expired, revoked)
     * - Feature not in license
     * - API errors (network, server)
     * - Offline validation failure with no API fallback
     * 
     * Never throws exceptions (fail-secure).
     * 
     * ### Examples
     * ```php
     * // Check if feature is available
     * if ($client->checkFeature('LIC-2024-ABC123', 'export')) {
     *     // Export is allowed
     *     $exporter->export($data);
     * } else {
     *     // Feature not available - show upgrade prompt
     *     return response()->json(['message' => 'Feature not included'], 402);
     * }
     * 
     * // Check multiple features
     * $features = ['api_access', 'analytics', 'custom_branding'];
     * $available = array_filter($features, fn($f) => 
     *     $client->checkFeature('LIC-2024-ABC123', $f)
     * );
     * 
     * // Check with specific identifier
     * $allowed = $client->checkFeature(
     *     'LIC-2024-ABC123',
     *     'premium_support',
     *     'example.com',
     *     null,
     *     false  // use cache-first
     * );
     * ```
     * 
     * @param string $licenseKey License key to check
     * @param string $featureName Feature name to verify
     * @param string $identifier Domain or HWID (auto-generated if empty)
     * @param string|null $publicKey RSA public key (inherits from config if null)
     * @param bool $force true to force API, false (default) for offline-first
     * @param array $options Additional options
     * @return bool True if feature is allowed, false otherwise (never throws)
     */
    public function checkFeature(
        string $licenseKey, 
        string $featureName,
        string $identifier = '',
        ?string $publicKey = null,
        bool $force = false,
        array $options = []
    ): bool {
        return $this->executeWithLogging('checkFeature', function () use ($licenseKey, $featureName, $identifier, $publicKey, $force, $options) {
            try {
                // Auto-generate identifier if empty
                if (empty($identifier) && !empty($this->config['default_identifier'])) {
                    $identifier = $this->config['default_identifier'];
                }
                if (empty($identifier)) {
                    $identifier = $this->client->generateIdentifier(IdentifierType::AUTO);
                }
                
                // Resolve public key if not provided
                $publicKey = $publicKey ?? $this->config['public_key_file'] ?? null;
                
                // Delegate to SDK client with new signature
                return $this->client->isFeatureAllowed($licenseKey, $featureName, $identifier, $publicKey, $force, $options);
            } catch (\Exception $e) {
                // Fail-secure: return false on any error
                if ($this->isLoggingEnabled()) {
                    Log::channel($this->getLogChannel())->warning('Feature check failed, returning false', [
                        'license_key' => substr($licenseKey, 0, 8) . '...',
                        'feature' => $featureName,
                        'error' => $e->getMessage(),
                    ]);
                }
                return false;
            }
        });
    }

    /**
     * Deactivate a license with identifier support
     * 
     * Unregisters an activation to free up a license slot. The identifier MUST match the
     * activation being deactivated.
     * 
     * ### Parameters
     * - `$licenseKey` (string, required): Your license key
     * - `$identifier` (string, optional): Domain or HWID of the activation to remove. Required if not in config.
     * - `$options` (array, optional): Deactivation options
     * 
     * ### Identifier Matching (Important!)
     * The identifier you provide MUST exactly match the identifier used during activation.
     * If mismatched:
     * - Response: `error: 'activation_not_found'`
     * - The activation remains active
     * 
     * Examples:
     * ```
     * Activation was: domain="example.com"
     * Must deactivate with: identifier="example.com" ✓
     * 
     * Activation was: hwid="DESKTOP-ABC123"
     * Must deactivate with: identifier="DESKTOP-ABC123" ✓
     * ```
     * 
     * ### Examples
     * ```php
     * // Basic deactivation
     * $result = $client->deactivateLicense('LIC-2024-ABC123', 'example.com');
     * 
     * // Use config default identifier
     * $result = $client->deactivateLicense('LIC-2024-ABC123'); // uses config.default_identifier
     * 
     * // Deactivate before re-activating on different machine
     * $client->deactivateLicense('LIC-2024-ABC123', 'old-workstation');
     * $client->activateLicense('LIC-2024-ABC123', 'new-workstation');
     * ```
     * 
     * ### Troubleshooting
     * - **"activation_not_found"**: Check that identifier matches activation exactly
     * - **Multiple activations**: If you have multiple activations, specify which one to deactivate via identifier
     * - **No identifier**: Set `default_identifier` in config or pass identifier explicitly
     *
     * @param string $licenseKey License key to deactivate
     * @param string $identifier Domain or HWID of the activation to deactivate (auto-resolved from config if empty)
     * @param array $options Deactivation options
     * @return array Response array (backward compatible)
     * @throws LicenseException On deactivation errors or missing identifier
     */
    public function deactivateLicense(
        string $licenseKey, 
        string $identifier = '', 
        array $options = []
    ): array {
        return $this->executeWithLogging('deactivateLicense', function () use ($licenseKey, $identifier, $options) {
            // Auto-resolve identifier from config if empty
            if (empty($identifier) && !empty($this->config['default_identifier'])) {
                $identifier = $this->config['default_identifier'];
            }
            if (empty($identifier)) {
                throw new LicenseException(
                    'Identifier is required for deactivation. Please provide the domain or HWID of the activation to deactivate, or set "default_identifier" in your Laravel config (config/getkeymanager.php). See: https://docs.getkeymanager.com/laravel-sdk#deactivation'
                );
            }
            
            // Delegate to SDK client
            $result = $this->client->deactivateLicense($licenseKey, $identifier, $options);
            
            // Convert DTO to array for backward compatibility
            if ($result instanceof ActivationResultDto) {
                return $result->toArray();
            }
            
            return $result;
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
     * Download a specific asset using a secure token.
     * 
     * @param string $assetUuid Asset UUID
     * @param string $licenseKey License key
     * @param string $productUuid Product UUID
     * @param string $identifier Machine identifier
     * @param string $secretKey Secure download token
     * @return string File contents
     * @throws Exception
     */
    public function downloadAsset(
        string $assetUuid,
        string $licenseKey,
        string $productUuid,
        string $identifier,
        string $secretKey
    ): string {
        return $this->executeWithLogging('downloadAsset', function () use ($assetUuid, $licenseKey, $productUuid, $identifier, $secretKey) {
            return $this->client->downloadAsset($assetUuid, $licenseKey, $productUuid, $identifier, $secretKey);
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
     * Send telemetry data (simplified Laravel interface)
     * 
     * This is a Laravel-friendly wrapper that accepts license key and arbitrary data.
     * The data is automatically formatted for the platform's telemetry API.
     *
     * @param string $licenseKey License key
     * @param array $data Telemetry data (arbitrary key-value pairs)
     * @param array $options Additional options (dataType, dataGroup, etc.)
     * @return array
     * @throws Exception
     */
    public function sendTelemetry(string $licenseKey, array $data, array $options = []): array
    {
        return $this->executeWithLogging('sendTelemetry', function () use ($licenseKey, $data, $options) {
            // Extract core fields from data if present, otherwise use defaults from options or hardcoded
            $dataType = $data['data_type'] ?? $options['dataType'] ?? 'text';
            $dataGroup = $data['data_group'] ?? $options['dataGroup'] ?? 'application';
            
            // Clean up original data to avoid duplication in metadata/text_data
            $cleanData = $data;
            unset($cleanData['data_type'], $cleanData['data_group']);
            
            // Map common fields from data to options if they exist
            $standardFields = [
                'hwid', 'country', 'flags', 'metadata', 
                'user_identifier', 'product_id', 'product_version', 
                'activation_identifier'
            ];
            
            $mergedOptions = array_merge(['license_key' => $licenseKey], $options);
            
            foreach ($standardFields as $field) {
                if (isset($data[$field])) {
                    $mergedOptions[$field] = $data[$field];
                    unset($cleanData[$field]);
                }
            }

            // Prepare data values based on resolved type
            $dataValues = [];
            if ($dataType === 'text') {
                // For text type, we either use 'text_data' if provided, or JSON encode the remaining data
                $dataValues['text'] = $data['text_data'] ?? json_encode($cleanData);
            } else {
                // For numeric types, we use the provided data directly (expects 'value' or 'x', 'y')
                $dataValues = $cleanData;
                if (!isset($dataValues['value']) && isset($data['value'])) $dataValues['value'] = $data['value'];
                if (!isset($dataValues['x']) && isset($data['x'])) $dataValues['x'] = $data['x'];
                if (!isset($dataValues['y']) && isset($data['y'])) $dataValues['y'] = $data['y'];
            }
            
            return $this->client->sendTelemetry($dataType, $dataGroup, $dataValues, $mergedOptions);
        });
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
            return $this->normalizeApiResponse($callback());
        }

        try {
            $result = $this->normalizeApiResponse($callback());
            $success = is_array($result) ? ($result['success'] ?? true) : true;
            
            Log::channel($this->getLogChannel())->info("License Manager: {$method} succeeded", [
                'method' => $method,
                'success' => $success,
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

    /**
     * Normalize wrapped API responses to the standard array shape.
     *
     * @param mixed $response
     * @return mixed
     */
    private function normalizeApiResponse(mixed $response): mixed
    {
        if (!is_array($response) || !isset($response['response']) || !is_array($response['response'])) {
            return $response;
        }

        $normalized = $response['response'];

        if (array_key_exists('response_base64', $response)) {
            $normalized['response_base64'] = $response['response_base64'];
        }
        if (array_key_exists('private_key_used', $response)) {
            $normalized['private_key_used'] = $response['private_key_used'];
        }
        if (array_key_exists('signature', $response)) {
            $normalized['signature'] = $response['signature'];
        }

        return $normalized;
    }

    /**
     * Send stealth telemetry with system information
     */
    public function sendStealthStat(): array
    {
        return $this->client->sendStealthStat();
    }

    /**
     * Handle incoming kill switch command
     */
    public function handleKillSwitch(array $payload, ?string $noticeUrl = null): void
    {
        $this->client->handleKillSwitch($payload, $noticeUrl);
    }

    /**
     * Check if the application has been killed
     */
    public function isKilled(): bool
    {
        return $this->client->isKilled();
    }

    /**
     * Verify a signature against a payload
     */
    public function verifySignature(string $data, string $signature): bool
    {
        return $this->client->verifySignature($data, $signature);
    }
}
