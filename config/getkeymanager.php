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
    | Default: https://dev.getkeymanager.com/api
    |
    */
    'base_url' => env('LICENSE_MANAGER_BASE_URL', 'https://dev.getkeymanager.com/api'),

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
    | Product Details
    |--------------------------------------------------------------------------
    |
    | These values are used for telemetry, updates, and identifying the
    | current installation state.
    |
    */
    'product_version' => env('LICENSE_MANAGER_PRODUCT_VERSION', ''),
    'product_numeric_version' => env('LICENSE_MANAGER_PRODUCT_NUMERIC_VERSION', null),
    'license_key' => env('LICENSE_MANAGER_LICENSE_KEY', ''),

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
    | Public Key File Path
    |--------------------------------------------------------------------------
    |
    | Path to the RSA public key file for signature verification AND offline
    | license file validation. Required if signature verification is enabled
    | or if you want to support offline .lic file validation.
    | 
    | The key should be stored in a .pem file. The application will
    | automatically load the key from this path.
    |
    | This same key is used for:
    | 1. Verifying API response signatures
    | 2. Decrypting and validating offline .lic files
    |
    | Example paths:
    | - storage/app/keys/product_public.pem
    | - config/keys/public_key.pem
    | - ~/.ssh/getkeymanager_public_key.pem
    |
    */
    'public_key_file' => env('LICENSE_MANAGER_PUBLIC_KEY_FILE', null),

    /*
    |--------------------------------------------------------------------------
    | License File Path (For Offline Validation)
    |--------------------------------------------------------------------------
    |
    | Directory where downloaded .lic files are stored for offline validation.
    | When configured, LicenseClient will first try to validate using a
    | cached .lic file before falling back to API calls.
    |
    | This enables fast, offline-first license validation even without
    | network connectivity (within expiry bounds).
    |
    | The application requires read access to this directory.
    |
    | Example paths:
    | - storage/app/licenses
    | - storage/licenses
    | - /var/cache/app/licenses
    |
    | If not configured, offline validation will be skipped and the SDK
    | will always call the API for license validation.
    |
    */
    'license_file_path' => env('LICENSE_MANAGER_LICENSE_FILE_PATH', null),

    /*
    |--------------------------------------------------------------------------
    | Default Identifier (For Configuration Inheritance)
    |--------------------------------------------------------------------------
    |
    | Default domain or hardware ID used across license operations.
    | When not explicitly provided to validate(), activate(), or deactivate()
    | methods, this value will be used automatically.
    |
    | This reduces repetitive parameter passing in single-tenant or
    | single-device scenarios.
    |
    | Example values:
    | - 'example.com' (for web applications)
    | - 'server-01.internal' (for server installations)
    | - null (auto-detect based on environment)
    |
    | If null, identifiers are auto-generated based on context:
    | - Web requests: $_SERVER['HTTP_HOST']
    | - CLI/Background: hardware ID of current machine
    |
    */
    'default_identifier' => env('LICENSE_MANAGER_DEFAULT_IDENTIFIER', null),

    /*
    |--------------------------------------------------------------------------
    | Public Key (Legacy - Deprecated)
    |--------------------------------------------------------------------------
    |
    | Deprecated: Use 'public_key_file' instead.
    | If 'public_key_file' is not set, this will be checked for backward
    | compatibility. However, reading from a file is the recommended approach.
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

        // Redirect URL for killed/pirated applications (Kill Switch)
        'killed_redirect_url' => env('LICENSE_MANAGER_KILLED_REDIRECT_URL', 'https://getkeymanager.com/legal-notice'),

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

    /*
    |--------------------------------------------------------------------------
    | State Cache TTL (Hardening Feature)
    |--------------------------------------------------------------------------
    |
    | Time-to-live in seconds for cached LicenseState objects.
    | This is separate from the API response cache and is used for
    | hardened license state management.
    |
    */
    'state_cache_ttl' => env('LICENSE_MANAGER_STATE_CACHE_TTL', 3600), // 1 hour

    /*
    |--------------------------------------------------------------------------
    | Product ID (Optional)
    |--------------------------------------------------------------------------
    |
    | Default product ID for license operations. Can be overridden per-request.
    |
    */
    'product_id' => env('LICENSE_MANAGER_PRODUCT_ID', null),

    /*
    |--------------------------------------------------------------------------
    | Grace Period Hours (Hardening Feature)
    |--------------------------------------------------------------------------
    |
    | Number of hours to allow grace period when license verification fails
    | due to network errors. Set to 0 to disable grace period.
    |
    */
    'grace_period_hours' => env('LICENSE_MANAGER_GRACE_PERIOD_HOURS', 72), // 3 days
];
