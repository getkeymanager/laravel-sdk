<?php

declare(strict_types=1);

namespace GetKeyManager\Laravel\Core\Exceptions;

/**
 * Revoked Exception - License has been revoked
 * 
 * @package GetKeyManager\SDK\Exceptions
 */
class RevokedException extends LicenseStatusException
{
    public const ERROR_LICENSE_REVOKED = 'LICENSE_REVOKED';
}
