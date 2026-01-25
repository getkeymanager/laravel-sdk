<?php

declare(strict_types=1);

namespace GetKeyManager\Laravel\Core;

/**
 * LicenseState - Public License State API
 * 
 * This class provides the public-facing API for checking license state
 * and capabilities. It wraps the internal EntitlementState.
 * 
 * @package GetKeyManager\Laravel\Core
 */
class LicenseState
{
    private EntitlementState $entitlementState;
    private ?string $licenseKey;

    /**
     * Create a LicenseState
     * 
     * @param EntitlementState $entitlementState Internal state
     * @param string|null $licenseKey Associated license key
     */
    public function __construct(EntitlementState $entitlementState, ?string $licenseKey = null)
    {
        $this->entitlementState = $entitlementState;
        $this->licenseKey = $licenseKey;
    }

    /**
     * Check if license is valid (active or in grace period)
     * 
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->entitlementState->allowsOperation();
    }

    /**
     * Check if license is active
     * 
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->entitlementState->isActive();
    }

    /**
     * Check if license is in grace period
     * 
     * @return bool
     */
    public function isInGracePeriod(): bool
    {
        return $this->entitlementState->isInGrace();
    }

    /**
     * Check if license has expired
     * 
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->entitlementState->isExpired();
    }

    /**
     * Check if a feature is allowed
     * 
     * @param string $feature Feature name
     * @return bool
     */
    public function allows(string $feature): bool
    {
        return $this->entitlementState->hasCapability($feature);
    }

    /**
     * Get feature value (for limits, quotas, etc.)
     * 
     * @param string $feature Feature name
     * @return mixed|null
     */
    public function getFeatureValue(string $feature)
    {
        return $this->entitlementState->getCapabilityValue($feature);
    }

    /**
     * Check if updates are allowed
     * 
     * @return bool
     */
    public function canUpdate(): bool
    {
        return $this->allows('updates');
    }

    /**
     * Check if downloads are allowed
     * 
     * @return bool
     */
    public function canDownload(): bool
    {
        return $this->allows('downloads');
    }

    /**
     * Check if telemetry should be sent
     * 
     * @return bool
     */
    public function canSendTelemetry(): bool
    {
        return $this->allows('telemetry');
    }

    /**
     * Get current state (ACTIVE, GRACE, RESTRICTED, INVALID)
     * 
     * @return string
     */
    public function getState(): string
    {
        return $this->entitlementState->getState();
    }

    /**
     * Get human-readable status message
     * 
     * @return string
     */
    public function getStatusMessage(): string
    {
        $state = $this->entitlementState->getState();

        switch ($state) {
            case EntitlementState::STATE_ACTIVE:
                return 'License is active and valid';
            
            case EntitlementState::STATE_GRACE:
                return 'License is in grace period - please revalidate soon';
            
            case EntitlementState::STATE_RESTRICTED:
                return 'License is restricted - activation or validation required';
            
            case EntitlementState::STATE_INVALID:
                return 'License is invalid or has been revoked';
            
            default:
                return 'License status unknown';
        }
    }

    /**
     * Get expiration timestamp
     * 
     * @return int|null Null if lifetime license
     */
    public function getExpiresAt(): ?int
    {
        $validity = $this->entitlementState->getValidityWindow();
        return $validity['until'];
    }

    /**
     * Get days until expiration
     * 
     * @return int|null Null if lifetime license, negative if expired
     */
    public function getDaysUntilExpiration(): ?int
    {
        $expiresAt = $this->getExpiresAt();
        
        if ($expiresAt === null) {
            return null; // Lifetime
        }

        $diff = $expiresAt - time();
        return (int)floor($diff / 86400);
    }

    /**
     * Check if license needs revalidation
     * 
     * @param int $intervalSeconds Revalidation interval (default: 24 hours)
     * @return bool
     */
    public function needsRevalidation(int $intervalSeconds = 86400): bool
    {
        return $this->entitlementState->needsRevalidation($intervalSeconds);
    }

    /**
     * Get all features/capabilities
     * 
     * @return array
     */
    public function getFeatures(): array
    {
        return $this->entitlementState->getCapabilities();
    }

    /**
     * Get license metadata
     * 
     * @return array
     */
    public function getMetadata(): array
    {
        return $this->entitlementState->getMetadata();
    }

    /**
     * Get the license key
     * 
     * @return string|null
     */
    public function getLicenseKey(): ?string
    {
        return $this->licenseKey;
    }

    /**
     * Verify context binding (domain or hardware ID)
     * 
     * @param string $context Current context to verify
     * @return bool
     */
    public function verifyContext(string $context): bool
    {
        return $this->entitlementState->verifyContextBinding($context);
    }

    /**
     * Get the internal entitlement state
     * 
     * @internal For SDK internal use only
     * @return EntitlementState
     */
    public function getEntitlementState(): EntitlementState
    {
        return $this->entitlementState;
    }

    /**
     * Convert to array representation
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'license_key' => $this->licenseKey,
            'is_valid' => $this->isValid(),
            'is_active' => $this->isActive(),
            'is_in_grace_period' => $this->isInGracePeriod(),
            'is_expired' => $this->isExpired(),
            'state' => $this->getState(),
            'status_message' => $this->getStatusMessage(),
            'expires_at' => $this->getExpiresAt(),
            'days_until_expiration' => $this->getDaysUntilExpiration(),
            'features' => $this->getFeatures(),
            'metadata' => $this->getMetadata(),
        ];
    }

    /**
     * Get JSON representation
     * 
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Magic method for readable output
     * 
     * @return string
     */
    public function __toString(): string
    {
        return $this->getStatusMessage();
    }
}
