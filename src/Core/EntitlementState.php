<?php

declare(strict_types=1);

namespace GetKeyManager\Laravel\Core;

use InvalidArgumentException;

/**
 * EntitlementState - Internal State Representation
 * 
 * This class represents the internal entitlement state of a license.
 * It uses domain-agnostic terminology to avoid obvious tampering targets.
 * 
 * @internal This class is for internal SDK use only
 * @package GetKeyManager\Laravel\Core
 */
class EntitlementState
{
    // State constants
    public const STATE_ACTIVE = 'ACTIVE';
    public const STATE_GRACE = 'GRACE';
    public const STATE_RESTRICTED = 'RESTRICTED';
    public const STATE_INVALID = 'INVALID';

    private string $state;
    private array $capabilities;
    private ?int $validFrom;
    private ?int $validUntil;
    private ?string $contextBinding; // domain_hash or hardware_id
    private ?string $productId;
    private ?string $environment;
    private array $metadata;
    private ?string $signature;
    private int $issuedAt;
    private int $lastVerifiedAt;

    /**
     * Create an EntitlementState from a validated API response
     * 
     * @param array $payload Response payload from API
     * @param string $signature Cryptographic signature
     * @throws InvalidArgumentException
     */
    public function __construct(array $payload, ?string $signature = null)
    {
        $this->validatePayload($payload);
        
        $this->state = $this->determineState($payload);
        $this->capabilities = $this->extractCapabilities($payload);
        $this->validFrom = $payload['valid_from'] ?? null;
        $this->validUntil = $payload['valid_until'] ?? null;
        $this->contextBinding = $payload['context_binding'] ?? null;
        $this->productId = $payload['product_id'] ?? null;
        $this->environment = $payload['environment'] ?? null;
        $this->metadata = $payload['metadata'] ?? [];
        $this->signature = $signature;
        $this->issuedAt = $payload['issued_at'] ?? time();
        $this->lastVerifiedAt = time();
    }

    /**
     * Create from cached data (must verify signature)
     * 
     * @param array $cachedData Previously cached state
     * @param SignatureVerifier|null $verifier Signature verifier
     * @return self
     * @throws SignatureException
     */
    public static function fromCache(array $cachedData, ?SignatureVerifier $verifier = null): self
    {
        if (!isset($cachedData['signature'])) {
            throw new SignatureException('Cached state missing signature');
        }

        // Verify signature on every read
        if ($verifier) {
            $signature = $cachedData['signature'];
            unset($cachedData['signature']);
            
            $payload = json_encode($cachedData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if (!$verifier->verify($payload, $signature)) {
                throw new SignatureException('Cached state signature verification failed');
            }
            
            $cachedData['signature'] = $signature;
        }

        return new self($cachedData, $cachedData['signature']);
    }

    /**
     * Check if a specific capability is allowed
     * 
     * @param string $capability Capability name (e.g., 'updates', 'telemetry')
     * @return bool
     */
    public function hasCapability(string $capability): bool
    {
        return isset($this->capabilities[$capability]) && $this->capabilities[$capability] === true;
    }

    /**
     * Get capability value (for limits, quotas, etc.)
     * 
     * @param string $capability Capability name
     * @return mixed|null
     */
    public function getCapabilityValue(string $capability)
    {
        return $this->capabilities[$capability] ?? null;
    }

    /**
     * Check if state is currently active
     * 
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->state === self::STATE_ACTIVE;
    }

    /**
     * Check if state is in grace period
     * 
     * @return bool
     */
    public function isInGrace(): bool
    {
        return $this->state === self::STATE_GRACE;
    }

    /**
     * Check if state allows operation (active or grace)
     * 
     * @return bool
     */
    public function allowsOperation(): bool
    {
        return $this->state === self::STATE_ACTIVE || $this->state === self::STATE_GRACE;
    }

    /**
     * Check if state has expired
     * 
     * @return bool
     */
    public function isExpired(): bool
    {
        if ($this->validUntil === null) {
            return false; // Lifetime license
        }

        return time() > $this->validUntil;
    }

    /**
     * Check if state needs revalidation
     * 
     * @param int $revalidationInterval Interval in seconds (default: 24 hours)
     * @return bool
     */
    public function needsRevalidation(int $revalidationInterval = 86400): bool
    {
        return (time() - $this->lastVerifiedAt) > $revalidationInterval;
    }

    /**
     * Get current state
     * 
     * @return string
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * Get all capabilities
     * 
     * @return array
     */
    public function getCapabilities(): array
    {
        return $this->capabilities;
    }

    /**
     * Get validity window
     * 
     * @return array{from: int|null, until: int|null}
     */
    public function getValidityWindow(): array
    {
        return [
            'from' => $this->validFrom,
            'until' => $this->validUntil,
        ];
    }

    /**
     * Get context binding (domain/hardware ID hash)
     * 
     * @return string|null
     */
    public function getContextBinding(): ?string
    {
        return $this->contextBinding;
    }

    /**
     * Verify context binding matches
     * 
     * @param string $context Current context (domain or hardware ID)
     * @return bool
     */
    public function verifyContextBinding(string $context): bool
    {
        if ($this->contextBinding === null) {
            return true; // Not bound
        }

        $contextHash = hash('sha256', $context);
        return hash_equals($this->contextBinding, $contextHash);
    }

    /**
     * Get metadata
     * 
     * @return array
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Convert to array for caching
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'state' => $this->state,
            'capabilities' => $this->capabilities,
            'valid_from' => $this->validFrom,
            'valid_until' => $this->validUntil,
            'context_binding' => $this->contextBinding,
            'product_id' => $this->productId,
            'environment' => $this->environment,
            'metadata' => $this->metadata,
            'signature' => $this->signature,
            'issued_at' => $this->issuedAt,
            'last_verified_at' => $this->lastVerifiedAt,
        ];
    }

    /**
     * Validate payload structure
     * 
     * @param array $payload
     * @throws InvalidArgumentException
     */
    private function validatePayload(array $payload): void
    {
        $required = ['issued_at'];
        
        foreach ($required as $field) {
            if (!isset($payload[$field]) && $field !== 'issued_at') {
                throw new InvalidArgumentException("Missing required field: {$field}");
            }
        }
    }

    /**
     * Determine state from payload
     * 
     * @param array $payload
     * @return string
     */
    private function determineState(array $payload): string
    {
        // Check if license data indicates invalid state
        if (isset($payload['status'])) {
            $status = strtolower($payload['status']);
            
            if (in_array($status, ['revoked', 'suspended', 'cancelled'])) {
                return self::STATE_INVALID;
            }
        }

        // Check validity window
        $now = time();
        
        if (isset($payload['valid_from']) && $now < $payload['valid_from']) {
            return self::STATE_RESTRICTED;
        }

        if (isset($payload['valid_until']) && $now > $payload['valid_until']) {
            // Check if we're within grace period (7 days after expiry)
            $gracePeriod = 7 * 86400; // 7 days
            if (($now - $payload['valid_until']) < $gracePeriod) {
                return self::STATE_GRACE;
            }
            
            return self::STATE_RESTRICTED;
        }

        // Check if validation succeeded
        if (isset($payload['valid']) && $payload['valid'] === true) {
            return self::STATE_ACTIVE;
        }

        // Check if revalidation failed but cache is fresh
        if (isset($payload['revalidation_failed']) && $payload['revalidation_failed'] === true) {
            // If last verified within grace window, allow operation
            $lastVerified = $payload['last_verified_at'] ?? 0;
            $graceWindow = 72 * 3600; // 72 hours
            
            if ((time() - $lastVerified) < $graceWindow) {
                return self::STATE_GRACE;
            }
        }

        // Default to active if basic checks pass
        return self::STATE_ACTIVE;
    }

    /**
     * Extract capabilities from payload
     * 
     * @param array $payload
     * @return array
     */
    private function extractCapabilities(array $payload): array
    {
        $capabilities = [];

        // Extract from features field
        if (isset($payload['features']) && is_array($payload['features'])) {
            foreach ($payload['features'] as $feature => $value) {
                $capabilities[$feature] = $value;
            }
        }

        // Extract from license data
        if (isset($payload['license']['features']) && is_array($payload['license']['features'])) {
            foreach ($payload['license']['features'] as $feature => $value) {
                $capabilities[$feature] = $value;
            }
        }

        // Add standard capabilities
        $capabilities['updates'] = $this->determineUpdateCapability($payload);
        $capabilities['telemetry'] = $this->determineTelemetryCapability($payload);
        $capabilities['downloads'] = $this->determineDownloadCapability($payload);

        // Extract limits
        if (isset($payload['license']['activations_limit'])) {
            $capabilities['max_activations'] = (int)$payload['license']['activations_limit'];
        }

        if (isset($payload['license']['activations_count'])) {
            $capabilities['current_activations'] = (int)$payload['license']['activations_count'];
        }

        return $capabilities;
    }

    /**
     * Determine update capability
     * 
     * @param array $payload
     * @return bool
     */
    private function determineUpdateCapability(array $payload): bool
    {
        // Updates allowed if license is active and not expired
        if (isset($payload['valid']) && $payload['valid'] === false) {
            return false;
        }

        if (isset($payload['license']['status'])) {
            $status = strtolower($payload['license']['status']);
            if (in_array($status, ['revoked', 'suspended'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine telemetry capability
     * 
     * @param array $payload
     * @return bool
     */
    private function determineTelemetryCapability(array $payload): bool
    {
        // Telemetry allowed unless explicitly disabled
        if (isset($payload['features']['telemetry'])) {
            return (bool)$payload['features']['telemetry'];
        }

        return true; // Default to enabled
    }

    /**
     * Determine download capability
     * 
     * @param array $payload
     * @return bool
     */
    private function determineDownloadCapability(array $payload): bool
    {
        // Downloads require active or assigned status
        if (isset($payload['license']['status'])) {
            $status = strtolower($payload['license']['status']);
            return in_array($status, ['active', 'assigned', 'available']);
        }

        return isset($payload['valid']) && $payload['valid'] === true;
    }
}
