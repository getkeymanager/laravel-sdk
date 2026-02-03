<?php

declare(strict_types=1);

namespace GetKeyManager\Laravel\Core\Exceptions;

use Exception;
use Throwable;
use GetKeyManager\Laravel\Core\ApiResponseCode;

/**
 * Base License Exception
 * 
 * @package GetKeyManager\SDK\Exceptions
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
