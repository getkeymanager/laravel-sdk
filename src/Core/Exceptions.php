<?php

declare(strict_types=1);

namespace GetKeyManager\Laravel\Core;

use Exception;
use Throwable;

/**
 * Base License Exception
 * 
 * @package GetKeyManager\Laravel\Core
 */
class LicenseException extends Exception
{
    protected ?string $errorCode = null;
    protected array $errorDetails = [];
    protected ?array $responseData = null;
    protected ?int $apiResponseCode = null;

    /**
     * Create a LicenseException
     * 
     * @param string $message Error message
     * @param int $code HTTP status code or error code
     * @param string|null $errorCode Specific error code (e.g., 'LICENSE_EXPIRED')
     * @param array $errorDetails Additional error details
     * @param Throwable|null $previous Previous exception
     * @param int|null $apiResponseCode API response code from server (e.g., 205 for LICENSE_EXPIRED)
     */
    public function __construct(
        string $message = "",
        int $code = 0,
        ?string $errorCode = null,
        array $errorDetails = [],
        ?Throwable $previous = null,
        ?int $apiResponseCode = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $errorCode;
        $this->errorDetails = $errorDetails;
        $this->apiResponseCode = $apiResponseCode;
    }

    /**
     * Get the specific error code
     * 
     * @return string|null
     */
    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    /**
     * Get the API response code from the server
     * 
     * @return int|null The numeric API response code (e.g., 205 for LICENSE_EXPIRED)
     */
    public function getApiCode(): ?int
    {
        return $this->apiResponseCode;
    }

    /**
     * Get the API response code constant name
     * 
     * @return string|null The constant name (e.g., 'LICENSE_EXPIRED')
     */
    public function getApiCodeName(): ?string
    {
        if ($this->apiResponseCode === null) {
            return null;
        }
        return ApiResponseCode::getName($this->apiResponseCode);
    }

    /**
     * Set the API response code
     * 
     * @param int $apiResponseCode The API response code
     * @return self
     */
    public function setApiCode(int $apiResponseCode): self
    {
        $this->apiResponseCode = $apiResponseCode;
        return $this;
    }

    /**
     * Get error details
     * 
     * @return array
     */
    public function getErrorDetails(): array
    {
        return $this->errorDetails;
    }

    /**
     * Set full response data for debugging
     * 
     * @param array $responseData
     * @return self
     */
    public function setResponseData(array $responseData): self
    {
        $this->responseData = $responseData;
        return $this;
    }

    /**
     * Get full response data
     * 
     * @return array|null
     */
    public function getResponseData(): ?array
    {
        return $this->responseData;
    }

    /**
     * Check if this is a specific error code
     * 
     * @param string $errorCode Error code to check
     * @return bool
     */
    public function isErrorCode(string $errorCode): bool
    {
        return $this->errorCode === $errorCode;
    }

    /**
     * Get a user-friendly error message
     * 
     * @return string
     */
    public function getUserMessage(): string
    {
        return $this->message;
    }

    /**
     * Convert to array for logging/debugging
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'exception' => get_class($this),
            'message' => $this->message,
            'code' => $this->code,
            'error_code' => $this->errorCode,
            'api_response_code' => $this->apiResponseCode,
            'api_code_name' => $this->getApiCodeName(),
            'details' => $this->errorDetails,
            'file' => $this->file,
            'line' => $this->line,
        ];
    }
}

/**
 * Validation Exception - Invalid input or API key
 * 
 * @package GetKeyManager\Laravel\Core
 */
class ValidationException extends LicenseException
{
    public const ERROR_INVALID_LICENSE_KEY = 'INVALID_LICENSE_KEY';
    public const ERROR_INVALID_API_KEY = 'INVALID_API_KEY';
    public const ERROR_VALIDATION_ERROR = 'VALIDATION_ERROR';
    public const ERROR_MISSING_PARAMETER = 'MISSING_PARAMETER';
}

/**
 * Network Exception - HTTP/network errors
 * 
 * @package GetKeyManager\Laravel\Core
 */
class NetworkException extends LicenseException
{
    public const ERROR_NETWORK_ERROR = 'NETWORK_ERROR';
    public const ERROR_TIMEOUT_ERROR = 'TIMEOUT_ERROR';
    public const ERROR_CONNECTION_ERROR = 'CONNECTION_ERROR';
    public const ERROR_DNS_ERROR = 'DNS_ERROR';
}

/**
 * Signature Exception - Signature verification failed
 * 
 * @package GetKeyManager\Laravel\Core
 */
class SignatureException extends LicenseException
{
    public const ERROR_SIGNATURE_VERIFICATION_FAILED = 'SIGNATURE_VERIFICATION_FAILED';
    public const ERROR_SIGNATURE_MISSING = 'SIGNATURE_MISSING';
    public const ERROR_INVALID_PUBLIC_KEY = 'INVALID_PUBLIC_KEY';
}

/**
 * Rate Limit Exception - Too many requests
 * 
 * @package GetKeyManager\Laravel\Core
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

/**
 * License Status Exception - Base for status-related errors
 * 
 * @package GetKeyManager\Laravel\Core
 */
class LicenseStatusException extends LicenseException
{
}

/**
 * Expired Exception - License has expired
 * 
 * @package GetKeyManager\Laravel\Core
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

/**
 * Suspended Exception - License is suspended
 * 
 * @package GetKeyManager\Laravel\Core
 */
class SuspendedException extends LicenseStatusException
{
    public const ERROR_LICENSE_SUSPENDED = 'LICENSE_SUSPENDED';
}

/**
 * Revoked Exception - License has been revoked
 * 
 * @package GetKeyManager\Laravel\Core
 */
class RevokedException extends LicenseStatusException
{
    public const ERROR_LICENSE_REVOKED = 'LICENSE_REVOKED';
}

/**
 * Activation Exception - Activation-related errors
 * 
 * @package GetKeyManager\Laravel\Core
 */
class ActivationException extends LicenseException
{
    public const ERROR_ACTIVATION_LIMIT_REACHED = 'ACTIVATION_LIMIT_REACHED';
    public const ERROR_ALREADY_ACTIVATED = 'ALREADY_ACTIVATED';
    public const ERROR_NOT_ACTIVATED = 'NOT_ACTIVATED';
    public const ERROR_HARDWARE_MISMATCH = 'HARDWARE_MISMATCH';
    public const ERROR_DOMAIN_MISMATCH = 'DOMAIN_MISMATCH';
}

/**
 * Feature Exception - Feature-related errors
 * 
 * @package GetKeyManager\Laravel\Core
 */
class FeatureException extends LicenseException
{
    public const ERROR_FEATURE_NOT_FOUND = 'FEATURE_NOT_FOUND';
    public const ERROR_FEATURE_DISABLED = 'FEATURE_DISABLED';
    public const ERROR_FEATURE_LIMIT_EXCEEDED = 'FEATURE_LIMIT_EXCEEDED';
}

/**
 * State Exception - State-related errors
 * 
 * @package GetKeyManager\Laravel\Core
 */
class StateException extends LicenseException
{
    public const ERROR_INVALID_STATE = 'INVALID_STATE';
    public const ERROR_STATE_TRANSITION_NOT_ALLOWED = 'STATE_TRANSITION_NOT_ALLOWED';
    public const ERROR_GRACE_PERIOD_EXPIRED = 'GRACE_PERIOD_EXPIRED';
}
