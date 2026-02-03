<?php

declare(strict_types=1);

namespace GetKeyManager\Laravel\Core\Exceptions;

/**
 * Rate Limit Exception - Too many requests
 * 
 * @package GetKeyManager\SDK\Exceptions
 */
class RateLimitException extends LicenseException
{
    public const ERROR_RATE_LIMIT_EXCEEDED = 'RATE_LIMIT_EXCEEDED';
    
    private ?int $retryAfter = null;

    /**
     * Set retry after seconds
     * 
     * @param int $seconds Seconds to wait before retry
     * @return self
     */
    public function setRetryAfter(int $seconds): self
    {
        $this->retryAfter = $seconds;
        return $this;
    }

    /**
     * Get retry after seconds
     * 
     * @return int|null
     */
    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }
}
