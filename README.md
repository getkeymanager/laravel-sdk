# KeyManager - License Manager Laravel SDK
For KeyManager (https://getkeymanager.com).

[![Latest Version](https://img.shields.io/packagist/v/getkeymanager/laravel-sdk.svg)](https://packagist.org/packages/getkeymanager/laravel-sdk)
[![License](https://img.shields.io/packagist/l/getkeymanager/laravel-sdk.svg)](https://packagist.org/packages/getkeymanager/laravel-sdk)
[![Total Downloads](https://img.shields.io/packagist/dt/getkeymanager/laravel-sdk.svg)](https://packagist.org/packages/getkeymanager/laravel-sdk)

**Version: 3.0.0** - Identifier-first with configuration inheritance and offline-first validation!

Official Laravel SDK for [License Management Platform](https://getkeymanager.com). Elegant license validation, activation, and management for Laravel applications with built-in middleware, Artisan commands, and facade support.

## Features

- 🚀 **Easy Integration** - Service provider auto-discovery, zero configuration
- 🧐 **Laravel-Native** - Facades, middleware, Artisan commands
- 🔐 **Route Protection** - Protect routes with license validation middleware
- 🎯 **Feature Flags** - Feature-gate middleware for license-based features
- 🔨 **CLI Commands** - Artisan commands for license operations
- 📏 **Full Logging** - Optional Laravel logging integration
- ⚡ **Session Caching** - Automatic session-based caching
- 🔄 **Laravel 10, 11, 12** - Multi-version compatibility
- **NEW v3.0.0:** Configuration inheritance, offline-first validation, identifier parameters, type-safe DTOs

## New in v3.0.0 - BREAKING CHANGES

- ✅ **Mandatory Identifier Parameter** - Use `default_identifier` in config or pass explicitly
- ✅ **Configuration Inheritance** - Set `license_file_path`, `default_identifier`, enhanced `public_key_file` in config
- ✅ **Offline-First Validation** - Automatically validates against cached .lic file before API
- ✅ **Type-Safe Methods** - Import and use Constants: `ValidationType`, `IdentifierType`
- ✅ **Enhanced Error Messages** - Actionable errors with documentation links
- ⚠️ **MIGRATION REQUIRED:** See migration guide in config file

## Requirements

- PHP 8.1 or higher
- Laravel 10.x, 11.x, or 12.x
- ext-json, ext-openssl, ext-curl

## Installation

Install via Composer:

```bash
composer require getkeymanager/laravel-sdk
```

### Publish Configuration (Optional)

```bash
php artisan vendor:publish --tag=getkeymanager-config
```

This creates `config/getkeymanager.php`.

### Environment Configuration

Add to your `.env` file:

```env
LICENSE_MANAGER_API_KEY=your-api-key-here
LICENSE_MANAGER_BASE_URL=https://api.getkeymanager.com
LICENSE_MANAGER_ENVIRONMENT=production
LICENSE_MANAGER_VERIFY_SIGNATURES=true
LICENSE_MANAGER_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----\n...\n-----END PUBLIC KEY-----"
```

## Quick Start

### Configuration Setup

Publish and configure in `config/getkeymanager.php`:

```php
'license_file_path' => env('LICENSE_FILE_PATH', 'storage/licenses'),
'default_identifier' => env('DEFAULT_IDENTIFIER', null), // or 'example.com'
'public_key_file' => storage_path('keys/public.pem'),
```

### Using the Facade

```php
use GetKeyManager\Laravel\Facades\GetKeyManager;
use GetKeyManager\SDK\Constants\ValidationType;

// Validate with auto-generated identifier
$result = GetKeyManager::validateLicense('XXXXX-XXXXX-XXXXX-XXXXX');

// Or validate with specific identifier
$result = GetKeyManager::validateLicense(
    'XXXXX-XXXXX-XXXXX-XXXXX',
    'example.com',  // Domain identifier
    null,           // Will use config's public key
    ValidationType::OFFLINE_FIRST  // Try cache first
);

if ($result['success']) {
    echo "License is valid!";
}

// Activate a license
$result = GetKeyManager::activateLicense(
    'XXXXX-XXXXX-XXXXX-XXXXX',
    'workstation-01'  // Required identifier
);

// Check a feature (fail-secure: returns false on any error)
if (GetKeyManager::checkFeature('XXXXX-XXXXX-XXXXX-XXXXX', 'advanced-features')) {
    echo "Feature is enabled!";
}
```

### Using Dependency Injection

```php
use GetKeyManager\Laravel\GetKeyManagerClient;

class LicenseController extends Controller
{
    public function validate(GetKeyManagerClient $license)
    {
        $result = $license->validateLicense(request('license_key'));
        
        return response()->json($result);
    }
}
```

## Middleware Protection

### Protect Routes with License Validation

```php
// In routes/web.php
Route::middleware(['license.validate'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/settings', [SettingsController::class, 'index']);
});

// Validate against a specific product
Route::get('/premium', function () {
    return view('premium');
})->middleware('license.validate:product-uuid-here');
```

### Feature-Gated Routes

```php
// Require a specific feature to be enabled
Route::get('/advanced-analytics', function () {
    return view('analytics.advanced');
})->middleware('license.feature:advanced-analytics');

// Chain middlewares
Route::middleware(['license.validate', 'license.feature:reporting'])
    ->group(function () {
        Route::get('/reports', [ReportController::class, 'index']);
    });
```

### Accessing License Data in Controllers

The middleware attaches license data to the request:

```php
class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $licenseData = $request->get('_license_data');
        $customerEmail = $licenseData['customer_email'] ?? 'Unknown';
        
        return view('dashboard', compact('licenseData', 'customerEmail'));
    }
}
```

### Custom Redirect on Validation Failure

Configure in `config/getkeymanager.php`:

```php
'middleware' => [
    'redirect_to' => '/license-required',
    'cache_in_session' => true,
],
```

### Providing License Key

The middleware accepts license keys from:
1. Header: `X-License-Key`
2. Query parameter: `?license_key=XXXXX`
3. Request body: `license_key` field
4. Session (cached from previous validation)

## Artisan Commands

### Validate a License

```bash
php artisan license:validate XXXXX-XXXXX-XXXXX-XXXXX

# With options
php artisan license:validate XXXXX-XXXXX-XXXXX-XXXXX \
    --hardware-id=custom-hwid \
    --product-id=product-uuid

# JSON output
php artisan license:validate XXXXX-XXXXX-XXXXX-XXXXX --json
```

### Activate a License

```bash
php artisan license:activate XXXXX-XXXXX-XXXXX-XXXXX

# With custom hardware ID
php artisan license:activate XXXXX-XXXXX-XXXXX-XXXXX \
    --hardware-id=server-001 \
    --name="Production Server"

# Domain-based activation
php artisan license:activate XXXXX-XXXXX-XXXXX-XXXXX \
    --domain=example.com
```

### Deactivate a License

```bash
# Deactivate by hardware ID
php artisan license:deactivate XXXXX-XXXXX-XXXXX-XXXXX \
    --hardware-id=server-001

# Deactivate specific activation
php artisan license:deactivate XXXXX-XXXXX-XXXXX-XXXXX \
    --activation-id=activation-uuid

# Deactivate all activations
php artisan license:deactivate XXXXX-XXXXX-XXXXX-XXXXX --all
```

## Advanced Usage

### Creating Licenses

```php
use GetKeyManager\Laravel\Facades\GetKeyManager;

$result = GetKeyManager::createLicenseKeys(
    'product-uuid',
    'generator-uuid',
    [
        ['activation_limit' => 5, 'validity_days' => 365],
        ['activation_limit' => 1, 'validity_days' => 30]
    ],
    'customer@example.com'
);
```

### Offline Validation

```php
// Read offline license file
$offlineLicense = file_get_contents('license.lic');

$result = GetKeyManager::validateOfflineLicense($offlineLicense, [
    'hardwareId' => GetKeyManager::generateHardwareId()
]);
```

### Getting License Details

```php
$details = GetKeyManager::getLicenseDetails('XXXXX-XXXXX-XXXXX-XXXXX');

// Get activations
$activations = GetKeyManager::getLicenseActivations('XXXXX-XXXXX-XXXXX-XXXXX');
```

### License Lifecycle Management

```php
// Suspend a license
GetKeyManager::suspendLicense('XXXXX-XXXXX-XXXXX-XXXXX');

// Resume a suspended license
GetKeyManager::resumeLicense('XXXXX-XXXXX-XXXXX-XXXXX');

// Revoke a license (permanent)
GetKeyManager::revokeLicense('XXXXX-XXXXX-XXXXX-XXXXX');
```

### Working with Metadata

```php
// Get license metadata
$metadata = GetKeyManager::getLicenseMetadata('XXXXX-XXXXX-XXXXX-XXXXX');

// Update metadata
GetKeyManager::updateLicenseMetadata('XXXXX-XXXXX-XXXXX-XXXXX', [
    'server_name' => 'Production 1',
    'deployment_date' => now()->toDateString()
]);

// Delete specific metadata key
GetKeyManager::deleteLicenseMetadata('XXXXX-XXXXX-XXXXX-XXXXX', 'server_name');
```

### Telemetry

```php
GetKeyManager::sendTelemetry('XXXXX-XXXXX-XXXXX-XXXXX', [
    'event' => 'feature_used',
    'feature_name' => 'export_pdf',
    'usage_count' => 1,
    'timestamp' => now()->toIso8601String()
]);
```

### Downloadables

```php
// Get product downloadables
$downloads = GetKeyManager::getDownloadables('product-uuid');

// Get download URL (authenticated)
$url = GetKeyManager::getDownloadUrl('downloadable-uuid', 'XXXXX-XXXXX-XXXXX-XXXXX');

// Redirect user to download
return redirect($url['data']['download_url']);
```

## Configuration Reference

Full configuration in `config/getkeymanager.php`:

```php
return [
    'api_key' => env('LICENSE_MANAGER_API_KEY'),
    'base_url' => env('LICENSE_MANAGER_BASE_URL', 'https://api.getkeymanager.com'),
    'environment' => env('LICENSE_MANAGER_ENVIRONMENT', 'production'),
    'verify_signatures' => env('LICENSE_MANAGER_VERIFY_SIGNATURES', true),
    'public_key' => env('LICENSE_MANAGER_PUBLIC_KEY'),
    'timeout' => env('LICENSE_MANAGER_TIMEOUT', 30),
    'cache_enabled' => env('LICENSE_MANAGER_CACHE_ENABLED', true),
    'cache_ttl' => env('LICENSE_MANAGER_CACHE_TTL', 300),
    'retry_attempts' => env('LICENSE_MANAGER_RETRY_ATTEMPTS', 3),
    'retry_delay' => env('LICENSE_MANAGER_RETRY_DELAY', 1000),
    
    'middleware' => [
        'redirect_to' => '/license-required',
        'cache_in_session' => true,
        'session_key' => 'license_validation',
    ],
    
    'logging' => [
        'enabled' => env('LICENSE_MANAGER_LOGGING', false),
        'channel' => env('LICENSE_MANAGER_LOG_CHANNEL', 'stack'),
    ],
];
```

## Error Handling

```php
use GetKeyManager\Laravel\Facades\GetKeyManager;
use Exception;

try {
    $result = GetKeyManager::validateLicense($licenseKey);
    
    if (!$result['success']) {
        // Handle validation failure
        $errorCode = $result['code'] ?? 0;
        $message = $result['message'] ?? 'Validation failed';
        
        // Handle specific error codes
        if ($errorCode === 4003) {
            return "License has expired";
        }
    }
} catch (Exception $e) {
    // Handle API errors
    Log::error('License validation error: ' . $e->getMessage());
    return "Unable to validate license";
}
```

## Testing

```php
use GetKeyManager\Laravel\Facades\GetKeyManager;

class FeatureTest extends TestCase
{
    public function test_license_validation()
    {
        $result = GetKeyManager::validateLicense('test-license-key');
        
        $this->assertTrue($result['success']);
        $this->assertEquals('active', $result['data']['status']);
    }
}
```

### Mocking in Tests

```php
use GetKeyManager\Laravel\Facades\GetKeyManager;

public function test_protected_route()
{
    GetKeyManager::shouldReceive('validateLicense')
        ->once()
        ->andReturn([
            'success' => true,
            'data' => ['status' => 'active']
        ]);
    
    $response = $this->get('/protected-route');
    $response->assertStatus(200);
}
```

## API Reference

The Laravel SDK proxies all methods from the base PHP SDK. See the [full API reference](https://docs.getkeymanager.com/sdks/php/api-reference).

### Core Methods

- `validateLicense(string $licenseKey, array $options = []): array`
- `activateLicense(string $licenseKey, array $options = []): array`
- `deactivateLicense(string $licenseKey, array $options = []): array`
- `checkFeature(string $licenseKey, string $featureName): array`
- `validateOfflineLicense($offlineLicenseData, array $options = []): array`

### License Management

- `createLicenseKeys(string $productUuid, string $generatorUuid, array $licenses, ?string $customerEmail, array $options = []): array`
- `updateLicenseKey(string $licenseKey, array $options = []): array`
- `getLicenseDetails(string $licenseKey): array`
- `getLicenseActivations(string $licenseKey): array`
- `suspendLicense(string $licenseKey): array`
- `resumeLicense(string $licenseKey): array`
- `revokeLicense(string $licenseKey): array`

### Utilities

- `generateHardwareId(): string`
- `generateUuid(): string`

## Examples

See the [examples directory](./examples) for complete working examples.

## Support

- **Website**: https://getkeymanager.com
- **Documentation**: https://docs.getkeymanager.com
- **API Reference**: https://dev.getkeymanager.com/api
- **Issues**: https://github.com/getkeymanager/laravel-sdk/issues
- **Email**: support@getkeymanager.com
## License

This SDK is open-sourced software licensed under the [MIT license](LICENSE).

## Related

- [Base PHP SDK](https://github.com/getkeymanager/php-sdk)
- [CodeIgniter SDK](https://github.com/getkeymanager/codeigniter-sdk)
- [License Management Platform](https://getkeymanager.com)
