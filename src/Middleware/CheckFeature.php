<?php

declare(strict_types=1);

namespace GetKeyManager\Laravel\Middleware;

use Closure;
use Illuminate\Http\Request;
use GetKeyManager\Laravel\Facades\GetKeyManager;
use GetKeyManager\Laravel\Core\Exceptions\LicenseException;
use GetKeyManager\Laravel\Core\ApiResponseCode;
use Exception;

/**
 * Check Feature Middleware (Hardened)
 * 
 * Protects routes by checking if a specific feature is enabled
 * for the provided license key using the hardened LicenseState API.
 * 
 * Version 2.0 - Hardened with LicenseState integration
 */
class CheckFeature
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $featureName  Feature name to check
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $featureName)
    {
        // Get license key from various sources
        $licenseKey = $this->getLicenseKey($request);

        if (!$licenseKey) {
            return $this->handleFeatureDisabled($request, 'License key is required', null);
        }

        try {
            // Use hardened feature check API
            $isAllowed = GetKeyManager::isFeatureAllowed($licenseKey, $featureName);

            if (!$isAllowed) {
                return $this->handleFeatureDisabled(
                    $request,
                    "Feature '{$featureName}' is not enabled for this license",
                    null
                );
            }

            // Attach feature info to request
            $request->merge([
                '_feature_name' => $featureName,
                '_feature_allowed' => true,
            ]);

            return $next($request);
        } catch (LicenseException $e) {
            return $this->handleLicenseException($request, $e, $featureName);
        } catch (Exception $e) {
            return $this->handleFeatureDisabled($request, $e->getMessage(), null);
        }
    }

    /**
     * Get license key from request
     *
     * @param Request $request
     * @return string|null
     */
    protected function getLicenseKey(Request $request): ?string
    {
        // Priority order: Header > Query > Body > Session > Previous middleware
        return $request->header('X-License-Key')
            ?? $request->query('license_key')
            ?? $request->input('license_key')
            ?? $request->session()->get('license_validation.license_key')
            ?? $request->get('_license_key');
    }

    /**
     * Handle license exception
     *
     * @param Request $request
     * @param LicenseException $exception
     * @param string $featureName
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    protected function handleLicenseException(Request $request, LicenseException $exception, string $featureName)
    {
        $apiCode = $exception->getApiCode();
        $message = "Feature '{$featureName}' check failed: " . $exception->getMessage();

        return $this->handleFeatureDisabled($request, $message, $apiCode);
    }

    /**
     * Handle disabled feature
     *
     * @param Request $request
     * @param string $message
     * @param int|null $apiCode
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    protected function handleFeatureDisabled(Request $request, string $message, ?int $apiCode = null)
    {
        // Handle JSON requests
        if ($request->expectsJson()) {
            $response = [
                'success' => false,
                'message' => $message,
            ];

            if ($apiCode !== null) {
                $response['api_code'] = $apiCode;
                $response['api_code_name'] = ApiResponseCode::getName($apiCode);
            }

            return response()->json($response, 403);
        }

        // Redirect with error message
        $redirectTo = config('getkeymanager.middleware.redirect_to', '/license-required');

        return redirect($redirectTo)
            ->with('error', $message)
            ->with('api_code', $apiCode);
    }
}
