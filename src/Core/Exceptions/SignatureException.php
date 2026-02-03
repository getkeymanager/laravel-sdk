<?php

declare(strict_types=1);

namespace GetKeyManager\Laravel\Core\Exceptions;

/**
 * Signature Exception - Signature verification failed
 * 
 * @package GetKeyManager\SDK\Exceptions
 */
class SignatureException extends LicenseException
{
    public const ERROR_SIGNATURE_VERIFICATION_FAILED = 'SIGNATURE_VERIFICATION_FAILED';
    public const ERROR_SIGNATURE_MISSING = 'SIGNATURE_MISSING';
    public const ERROR_INVALID_PUBLIC_KEY = 'INVALID_PUBLIC_KEY';
}
