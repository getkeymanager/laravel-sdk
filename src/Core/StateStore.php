<?php

declare(strict_types=1);

namespace GetKeyManager\Laravel\Core;

/**
 * StateStore - Internal State Cache Manager
 * 
 * Manages cached EntitlementState with signature verification
 * on every read operation.
 * 
 * @internal This class is for internal SDK use only
 * @package GetKeyManager\Laravel\Core
 */
class StateStore
{
    private array $store = [];
    private ?SignatureVerifier $verifier;
    private int $defaultTtl;

    /**
     * Initialize StateStore
     * 
     * @param SignatureVerifier|null $verifier Signature verifier for cache validation
     * @param int $defaultTtl Default TTL in seconds
     */
    public function __construct(?SignatureVerifier $verifier = null, int $defaultTtl = 300)
    {
        $this->verifier = $verifier;
        $this->defaultTtl = $defaultTtl;
    }

    /**
     * Store an EntitlementState
     * 
     * @param string $key Cache key
     * @param EntitlementState $state State to store
     * @param int|null $ttl TTL in seconds (null = use default)
     * @return void
     */
    public function set(string $key, EntitlementState $state, ?int $ttl = null): void
    {
        $ttl = $ttl ?? $this->defaultTtl;
        
        $this->store[$key] = [
            'state' => $state->toArray(),
            'expires_at' => time() + $ttl,
        ];
    }

    /**
     * Retrieve an EntitlementState (verifies signature on every read)
     * 
     * @param string $key Cache key
     * @return EntitlementState|null
     * @throws SignatureException If signature verification fails
     */
    public function get(string $key): ?EntitlementState
    {
        if (!isset($this->store[$key])) {
            return null;
        }

        $cached = $this->store[$key];

        // Check TTL expiration
        if ($cached['expires_at'] < time()) {
            unset($this->store[$key]);
            return null;
        }

        // Verify signature on every read
        try {
            return EntitlementState::fromCache($cached['state'], $this->verifier);
        } catch (SignatureException $e) {
            // Invalid signature - remove from cache
            unset($this->store[$key]);
            throw $e;
        }
    }

    /**
     * Check if a key exists and is not expired
     * 
     * @param string $key Cache key
     * @return bool
     */
    public function has(string $key): bool
    {
        if (!isset($this->store[$key])) {
            return false;
        }

        $cached = $this->store[$key];
        
        if ($cached['expires_at'] < time()) {
            unset($this->store[$key]);
            return false;
        }

        return true;
    }

    /**
     * Remove a cached state
     * 
     * @param string $key Cache key
     * @return void
     */
    public function remove(string $key): void
    {
        unset($this->store[$key]);
    }

    /**
     * Clear all cached states
     * 
     * @return void
     */
    public function clear(): void
    {
        $this->store = [];
    }

    /**
     * Clear all cached states for a license key
     * 
     * @param string $licenseKey License key
     * @return void
     */
    public function clearLicense(string $licenseKey): void
    {
        $prefix = $this->getLicenseKeyPrefix($licenseKey);
        
        foreach (array_keys($this->store) as $key) {
            if (strpos($key, $prefix) === 0) {
                unset($this->store[$key]);
            }
        }
    }

    /**
     * Get cache key for license validation
     * 
     * @param string $licenseKey License key
     * @return string
     */
    public function getValidationKey(string $licenseKey): string
    {
        return "entitlement:{$licenseKey}:validation";
    }

    /**
     * Get cache key for feature check
     * 
     * @param string $licenseKey License key
     * @param string $feature Feature name
     * @return string
     */
    public function getFeatureKey(string $licenseKey, string $feature): string
    {
        return "entitlement:{$licenseKey}:feature:{$feature}";
    }

    /**
     * Get license key prefix for bulk operations
     * 
     * @param string $licenseKey License key
     * @return string
     */
    private function getLicenseKeyPrefix(string $licenseKey): string
    {
        return "entitlement:{$licenseKey}:";
    }

    /**
     * Perform garbage collection (remove expired entries)
     * 
     * @return int Number of entries removed
     */
    public function gc(): int
    {
        $count = 0;
        $now = time();

        foreach ($this->store as $key => $cached) {
            if ($cached['expires_at'] < $now) {
                unset($this->store[$key]);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get statistics about the store
     * 
     * @return array
     */
    public function getStats(): array
    {
        $now = time();
        $expired = 0;
        $active = 0;

        foreach ($this->store as $cached) {
            if ($cached['expires_at'] < $now) {
                $expired++;
            } else {
                $active++;
            }
        }

        return [
            'total' => count($this->store),
            'active' => $active,
            'expired' => $expired,
        ];
    }
}
