<?php

declare(strict_types=1);

namespace GetKeyManager\Laravel\Core\Exceptions;

/**
 * Activation Exception - Activation-related errors
 * 
 * @package GetKeyManager\SDK\Exceptions
 */
class ActivationException extends LicenseException
{
    public const ERROR_ACTIVATION_LIMIT_REACHED = 'ACTIVATION_LIMIT_REACHED';
    public const ERROR_ALREADY_ACTIVATED = 'ALREADY_ACTIVATED';
    public const ERROR_NOT_ACTIVATED = 'NOT_ACTIVATED';
    public const ERROR_HARDWARE_MISMATCH = 'HARDWARE_MISMATCH';
    public const ERROR_DOMAIN_MISMATCH = 'DOMAIN_MISMATCH';
}
