<?php

declare(strict_types=1);

namespace LicenseManager\Laravel\Middleware;

use Closure;
use Illuminate\Http\Request;
use LicenseManager\Laravel\Facades\LicenseManager;
use Exception;

/**
 * Check Feature Middleware
 * 
 * Protects routes by checking if a specific feature is enabled
 * for the provided license key.
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
            return $this->handleFeatureDisabled($request, 'License key is required');
        }

        try {
            // Check feature
            $result = LicenseManager::checkFeature($licenseKey, $featureName);

            // Check if feature is enabled
            if (!($result['success'] ?? false) || !($result['data']['enabled'] ?? false)) {
                return $this->handleFeatureDisabled(
                    $request,
                    "Feature '{$featureName}' is not enabled for this license"
                );
            }

            // Attach feature data to request
            $request->merge([
                '_feature_data' => $result['data'] ?? [],
                '_feature_name' => $featureName,
            ]);

            return $next($request);
        } catch (Exception $e) {
            return $this->handleFeatureDisabled($request, $e->getMessage());
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
        // Priority order: Header > Query > Body > Session > License Data (from previous middleware)
        return $request->header('X-License-Key')
            ?? $request->query('license_key')
            ?? $request->input('license_key')
            ?? $request->session()->get('license_validation.license_key')
            ?? ($request->get('_license_data')['license_key'] ?? null);
    }

    /**
     * Handle disabled feature
     *
     * @param Request $request
     * @param string $message
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    protected function handleFeatureDisabled(Request $request, string $message)
    {
        // Handle JSON requests
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 403);
        }

        // Redirect with error message
        $redirectTo = config('licensemanager.middleware.redirect_to', '/license-required');

        return redirect($redirectTo)->with('error', $message);
    }
}
