<?php

declare(strict_types=1);

namespace GetKeyManager\Laravel\Core\Exceptions;

/**
 * Feature Exception - Feature-related errors
 * 
 * @package GetKeyManager\SDK\Exceptions
 */
class FeatureException extends LicenseException
{
    public const ERROR_FEATURE_NOT_FOUND = 'FEATURE_NOT_FOUND';
    public const ERROR_FEATURE_DISABLED = 'FEATURE_DISABLED';
    public const ERROR_FEATURE_LIMIT_EXCEEDED = 'FEATURE_LIMIT_EXCEEDED';
}
