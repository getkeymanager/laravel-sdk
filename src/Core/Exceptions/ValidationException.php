<?php

declare(strict_types=1);

namespace GetKeyManager\Laravel\Core\Exceptions;

/**
 * Validation Exception - Invalid input or API key
 * 
 * @package GetKeyManager\SDK\Exceptions
 */
class ValidationException extends LicenseException
{
    public const ERROR_INVALID_LICENSE_KEY = 'INVALID_LICENSE_KEY';
    public const ERROR_INVALID_API_KEY = 'INVALID_API_KEY';
    public const ERROR_VALIDATION_ERROR = 'VALIDATION_ERROR';
    public const ERROR_MISSING_PARAMETER = 'MISSING_PARAMETER';
}
