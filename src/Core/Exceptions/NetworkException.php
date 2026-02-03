<?php

declare(strict_types=1);

namespace GetKeyManager\Laravel\Core\Exceptions;

/**
 * Network Exception - HTTP/network errors
 * 
 * @package GetKeyManager\SDK\Exceptions
 */
class NetworkException extends LicenseException
{
    public const ERROR_NETWORK_ERROR = 'NETWORK_ERROR';
    public const ERROR_TIMEOUT_ERROR = 'TIMEOUT_ERROR';
    public const ERROR_CONNECTION_ERROR = 'CONNECTION_ERROR';
    public const ERROR_DNS_ERROR = 'DNS_ERROR';
}
