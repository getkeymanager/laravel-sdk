<?php

declare(strict_types=1);

namespace GetKeyManager\Laravel\Core\Exceptions;

/**
 * Expired Exception - License has expired
 * 
 * @package GetKeyManager\SDK\Exceptions
 */
class ExpiredException extends LicenseStatusException
{
    public const ERROR_LICENSE_EXPIRED = 'LICENSE_EXPIRED';
    
    private ?int $expiredAt = null;

    /**
     * Set expiration timestamp
     * 
     * @param int $timestamp Expiration timestamp
     * @return self
     */
    public function setExpiredAt(int $timestamp): self
    {
        $this->expiredAt = $timestamp;
        return $this;
    }

    /**
     * Get expiration timestamp
     * 
     * @return int|null
     */
    public function getExpiredAt(): ?int
    {
        return $this->expiredAt;
    }

    /**
     * Get days since expiration
     * 
     * @return int
     */
    public function getDaysSinceExpiration(): int
    {
        if ($this->expiredAt === null) {
            return 0;
        }

        $diff = time() - $this->expiredAt;
        return (int)floor($diff / 86400);
    }
}
