<?php

declare(strict_types=1);

namespace GetKeyManager\Laravel\Core\Exceptions;

/**
 * State Exception - State-related errors
 * 
 * @package GetKeyManager\SDK\Exceptions
 */
class StateException extends LicenseException
{
    public const ERROR_INVALID_STATE = 'INVALID_STATE';
    public const ERROR_STATE_TRANSITION_NOT_ALLOWED = 'STATE_TRANSITION_NOT_ALLOWED';
    public const ERROR_GRACE_PERIOD_EXPIRED = 'GRACE_PERIOD_EXPIRED';
}
