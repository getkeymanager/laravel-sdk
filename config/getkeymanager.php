<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Key
    |--------------------------------------------------------------------------
    |
    | Your License Management Platform API key. This key is required for all
    | API operations. Get your API key from your admin dashboard.
    |
    */
    'api_key' => env('LICENSE_MANAGER_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for the License Management Platform API.
    | Default: https://api.getkeymanager.com
    |
    */
    'base_url' => env('LICENSE_MANAGER_BASE_URL', 'https://api.getkeymanager.com'),

    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    |
    | The environment to use for license operations. Must match your product
    | environment configuration.
    | Options: 'production', 'staging', 'development'
    |
    */
    'environment' => env('LICENSE_MANAGER_ENVIRONMENT', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Signature Verification
    |--------------------------------------------------------------------------
    |
    | Enable/disable cryptographic signature verification for API responses.
    | Highly recommended for production environments.
    |
    */
    'verify_signatures' => env('LICENSE_MANAGER_VERIFY_SIGNATURES', true),

    /*
    |--------------------------------------------------------------------------
    | Public Key
    |--------------------------------------------------------------------------
    |
    | RSA public key for signature verification. Required if signature
    | verification is enabled. Get this from your product settings.
    |
    */
    'public_key' => env('LICENSE_MANAGER_PUBLIC_KEY', null),

    /*
    |--------------------------------------------------------------------------
    | HTTP Timeout
    |--------------------------------------------------------------------------
    |
    | Request timeout in seconds for API calls.
    |
    */
    'timeout' => env('LICENSE_MANAGER_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | Enable response caching to improve performance and reduce API calls.
    |
    */
    'cache_enabled' => env('LICENSE_MANAGER_CACHE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Cache TTL
    |--------------------------------------------------------------------------
    |
    | Cache time-to-live in seconds for validation responses.
    |
    */
    'cache_ttl' => env('LICENSE_MANAGER_CACHE_TTL', 300),

    /*
    |--------------------------------------------------------------------------
    | Retry Settings
    |--------------------------------------------------------------------------
    |
    | Configure automatic retry behavior for failed API requests.
    |
    */
    'retry_attempts' => env('LICENSE_MANAGER_RETRY_ATTEMPTS', 3),
    'retry_delay' => env('LICENSE_MANAGER_RETRY_DELAY', 1000),

    /*
    |--------------------------------------------------------------------------
    | Middleware Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for license validation middleware.
    |
    */
    'middleware' => [
        // Redirect route when license validation fails
        'redirect_to' => env('LICENSE_MANAGER_REDIRECT_ROUTE', '/license-required'),

        // Cache license validation results in session
        'cache_in_session' => true,

        // Session key for storing validation results
        'session_key' => 'license_validation',
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Enable logging of license operations for debugging and audit purposes.
    |
    */
    'logging' => [
        'enabled' => env('LICENSE_MANAGER_LOGGING', false),
        'channel' => env('LICENSE_MANAGER_LOG_CHANNEL', 'stack'),
    ],
];
