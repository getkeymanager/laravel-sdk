<?php

/**
 * Laravel SDK Usage Examples
 * 
 * This file demonstrates various usage patterns for the License Manager Laravel SDK.
 */

require __DIR__ . '/vendor/autoload.php';

use GetKeyManager\Laravel\Facades\GetKeyManager;
use Illuminate\Support\Facades\Route;

// ============================================================================
// EXAMPLE 1: Basic Facade Usage
// ============================================================================

// Validate a license
$result = GetKeyManager::validateLicense('XXXXX-XXXXX-XXXXX-XXXXX', [
    'hardwareId' => GetKeyManager::generateHardwareId()
]);

if ($result['success']) {
    echo "✓ License is valid\n";
    echo "Status: " . $result['data']['status'] . "\n";
} else {
    echo "✗ Validation failed: " . $result['message'] . "\n";
}

// ============================================================================
// EXAMPLE 2: License Activation
// ============================================================================

$licenseKey = 'XXXXX-XXXXX-XXXXX-XXXXX';
$hardwareId = GetKeyManager::generateHardwareId();

$result = GetKeyManager::activateLicense($licenseKey, [
    'hardwareId' => $hardwareId,
    'name' => 'Production Server',
    'metadata' => [
        'environment' => 'production',
        'server_ip' => request()->ip()
    ]
]);

if ($result['success']) {
    $activation = $result['data']['activation'];
    echo "✓ License activated\n";
    echo "Activation ID: " . $activation['uuid'] . "\n";
    echo "Remaining activations: " . $result['data']['remaining_activations'] . "\n";
}

// ============================================================================
// EXAMPLE 3: Feature Checking
// ============================================================================

$result = GetKeyManager::checkFeature('XXXXX-XXXXX-XXXXX-XXXXX', 'advanced-reporting');

if ($result['data']['enabled']) {
    echo "✓ Advanced reporting is enabled\n";
    // Enable premium features
} else {
    echo "✗ Feature not available\n";
}

// ============================================================================
// EXAMPLE 4: Route Protection with Middleware
// ============================================================================

// Protect entire route groups
Route::middleware(['license.validate'])->group(function () {
    Route::get('/dashboard', 'DashboardController@index');
    Route::get('/reports', 'ReportController@index');
});

// Protect specific routes with product validation
Route::get('/premium-features', function () {
    return view('premium');
})->middleware('license.validate:product-uuid-here');

// Feature-gate specific routes
Route::get('/advanced-analytics', 'AnalyticsController@advanced')
    ->middleware('license.feature:advanced-analytics');

// Chain multiple middleware
Route::middleware(['license.validate', 'license.feature:api-access'])
    ->prefix('api')
    ->group(function () {
        Route::get('/export', 'ApiController@export');
    });

// ============================================================================
// EXAMPLE 5: Controller Integration
// ============================================================================

use Illuminate\Http\Request;
use GetKeyManager\Laravel\GetKeyManagerClient;

class LicenseController extends Controller
{
    private GetKeyManagerClient $license;

    public function __construct(GetKeyManagerClient $license)
    {
        $this->license = $license;
    }

    public function validate(Request $request)
    {
        $licenseKey = $request->input('license_key');
        
        try {
            $result = $this->license->validateLicense($licenseKey);
            
            if ($result['success']) {
                return response()->json([
                    'valid' => true,
                    'license' => $result['data']
                ]);
            }
            
            return response()->json([
                'valid' => false,
                'message' => $result['message']
            ], 400);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function activate(Request $request)
    {
        $validated = $request->validate([
            'license_key' => 'required|string',
            'hardware_id' => 'nullable|string',
            'domain' => 'nullable|string',
        ]);

        $result = $this->license->activateLicense(
            $validated['license_key'],
            array_filter([
                'hardwareId' => $validated['hardware_id'] ?? null,
                'domain' => $validated['domain'] ?? null,
            ])
        );

        return response()->json($result);
    }
}

// ============================================================================
// EXAMPLE 6: Accessing License Data in Views
// ============================================================================

// In your controller after middleware
class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Middleware attaches license data to request
        $licenseData = $request->get('_license_data');
        
        return view('dashboard', [
            'customer' => $licenseData['customer_email'] ?? 'Unknown',
            'activations' => $licenseData['activation_count'] ?? 0,
            'valid_until' => $licenseData['valid_until'] ?? null,
            'features' => $licenseData['features'] ?? [],
        ]);
    }
}

// In your Blade template (resources/views/dashboard.blade.php)
/*
@extends('layouts.app')

@section('content')
<div class="license-info">
    <h2>License Information</h2>
    <p>Customer: {{ $customer }}</p>
    <p>Activations: {{ $activations }}</p>
    
    @if($features['premium'] ?? false)
        <div class="premium-badge">Premium Features Enabled</div>
    @endif
</div>
@endsection
*/

// ============================================================================
// EXAMPLE 7: Offline License Validation
// ============================================================================

// Read offline license file
$offlineLicense = Storage::get('licenses/offline.lic');

$result = GetKeyManager::validateOfflineLicense($offlineLicense, [
    'hardwareId' => GetKeyManager::generateHardwareId()
]);

if ($result['success']) {
    echo "✓ Offline license is valid\n";
    echo "Valid until: " . $result['data']['valid_until'] . "\n";
}

// ============================================================================
// EXAMPLE 8: License Management
// ============================================================================

// Create bulk licenses
$result = GetKeyManager::createLicenseKeys(
    'product-uuid-here',
    'generator-uuid-here',
    [
        ['activation_limit' => 5, 'validity_days' => 365],
        ['activation_limit' => 1, 'validity_days' => 30],
    ],
    'customer@example.com'
);

echo "Created " . count($result['data']['licenses']) . " licenses\n";

// Update license
GetKeyManager::updateLicenseKey('XXXXX-XXXXX-XXXXX-XXXXX', [
    'activation_limit' => 10,
    'notes' => 'Upgraded to premium plan'
]);

// Suspend/Resume
GetKeyManager::suspendLicense('XXXXX-XXXXX-XXXXX-XXXXX');
GetKeyManager::resumeLicense('XXXXX-XXXXX-XXXXX-XXXXX');

// ============================================================================
// EXAMPLE 9: Metadata Management
// ============================================================================

// Store custom metadata with license
GetKeyManager::updateLicenseMetadata('XXXXX-XXXXX-XXXXX-XXXXX', [
    'server_name' => 'prod-server-01',
    'deployment_date' => now()->toDateString(),
    'admin_contact' => 'admin@example.com'
]);

// Retrieve metadata
$metadata = GetKeyManager::getLicenseMetadata('XXXXX-XXXXX-XXXXX-XXXXX');

// Delete specific metadata key
GetKeyManager::deleteLicenseMetadata('XXXXX-XXXXX-XXXXX-XXXXX', 'deployment_date');

// ============================================================================
// EXAMPLE 10: Telemetry
// ============================================================================

GetKeyManager::sendTelemetry('XXXXX-XXXXX-XXXXX-XXXXX', [
    'event' => 'feature_usage',
    'feature' => 'pdf_export',
    'count' => 1,
    'user_agent' => request()->userAgent(),
    'ip_address' => request()->ip(),
    'timestamp' => now()->toIso8601String()
]);

// ============================================================================
// EXAMPLE 11: Downloadables
// ============================================================================

// Get product downloads
$downloads = GetKeyManager::getDownloadables('product-uuid-here', [
    'version' => '2.0.0',
    'platform' => 'windows'
]);

foreach ($downloads['data']['downloadables'] as $download) {
    echo $download['name'] . " - " . $download['version'] . "\n";
}

// Get authenticated download URL
$result = GetKeyManager::getDownloadUrl(
    'downloadable-uuid-here',
    'XXXXX-XXXXX-XXXXX-XXXXX'
);

// Redirect user to download
return redirect($result['data']['download_url']);

// ============================================================================
// EXAMPLE 12: Error Handling
// ============================================================================

use Illuminate\Support\Facades\Log;

try {
    $result = GetKeyManager::validateLicense($licenseKey);
    
    if (!$result['success']) {
        // Handle specific error codes
        $errorCode = $result['code'] ?? 0;
        
        switch ($errorCode) {
            case 4001:
                return "License not found";
            case 4003:
                return "License has expired";
            case 4004:
                return "License is suspended";
            case 4005:
                return "License is revoked";
            case 4006:
                return "Activation limit reached";
            default:
                return $result['message'] ?? 'Validation failed';
        }
    }
    
} catch (\Exception $e) {
    Log::error('License validation error', [
        'license_key' => $licenseKey,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    return "Unable to validate license. Please try again later.";
}

// ============================================================================
// EXAMPLE 13: Queue Jobs for Background Processing
// ============================================================================

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class ValidateLicenseJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    private string $licenseKey;

    public function __construct(string $licenseKey)
    {
        $this->licenseKey = $licenseKey;
    }

    public function handle(GetKeyManagerClient $license)
    {
        $result = $license->validateLicense($this->licenseKey);
        
        // Store result, send notification, etc.
        if (!$result['success']) {
            // Handle invalid license
            Log::warning('Invalid license detected', [
                'license_key' => $this->licenseKey,
                'reason' => $result['message']
            ]);
        }
    }
}

// Dispatch the job
ValidateLicenseJob::dispatch('XXXXX-XXXXX-XXXXX-XXXXX');
