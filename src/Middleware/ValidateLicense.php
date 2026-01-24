<?php

declare(strict_types=1);

namespace LicenseManager\Laravel\Middleware;

use Closure;
use Illuminate\Http\Request;
use LicenseManager\Laravel\Facades\LicenseManager;
use Exception;

/**
 * Validate License Middleware
 * 
 * Protects routes by validating a license key from the request.
 * The license key can be provided via:
 * - Query parameter: ?license_key=XXXXX
 * - Request body: license_key field
 * - Session: stored from previous validation
 * - Header: X-License-Key
 */
class ValidateLicense
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $productId  Optional product ID to validate against
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ?string $productId = null)
    {
        // Get license key from various sources
        $licenseKey = $this->getLicenseKey($request);

        if (!$licenseKey) {
            return $this->handleInvalidLicense($request, 'License key is required');
        }

        // Check session cache first
        if ($this->isSessionCachingEnabled() && $this->isLicenseValidInSession($request, $licenseKey)) {
            return $next($request);
        }

        try {
            // Validate license
            $options = [];
            if ($productId) {
                $options['productId'] = $productId;
            }

            $result = LicenseManager::validateLicense($licenseKey, $options);

            // Check if validation was successful
            if (!($result['success'] ?? false)) {
                return $this->handleInvalidLicense(
                    $request,
                    $result['message'] ?? 'License validation failed'
                );
            }

            // Store in session if caching is enabled
            if ($this->isSessionCachingEnabled()) {
                $this->storeLicenseInSession($request, $licenseKey, $result);
            }

            // Attach license data to request for use in controllers
            $request->merge(['_license_data' => $result['data'] ?? []]);

            return $next($request);
        } catch (Exception $e) {
            return $this->handleInvalidLicense($request, $e->getMessage());
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
        // Priority order: Header > Query > Body > Session
        return $request->header('X-License-Key')
            ?? $request->query('license_key')
            ?? $request->input('license_key')
            ?? $request->session()->get($this->getSessionKey() . '.license_key');
    }

    /**
     * Check if session caching is enabled
     *
     * @return bool
     */
    protected function isSessionCachingEnabled(): bool
    {
        return config('getkeymanager.middleware.cache_in_session', true);
    }

    /**
     * Get session key for caching
     *
     * @return string
     */
    protected function getSessionKey(): string
    {
        return config('getkeymanager.middleware.session_key', 'license_validation');
    }

    /**
     * Check if license is valid in session
     *
     * @param Request $request
     * @param string $licenseKey
     * @return bool
     */
    protected function isLicenseValidInSession(Request $request, string $licenseKey): bool
    {
        $sessionData = $request->session()->get($this->getSessionKey());

        if (!$sessionData || !is_array($sessionData)) {
            return false;
        }

        // Check if the cached license matches and is not expired
        if (($sessionData['license_key'] ?? '') !== $licenseKey) {
            return false;
        }

        $cachedAt = $sessionData['cached_at'] ?? 0;
        $cacheTtl = config('getkeymanager.cache_ttl', 300);

        return (time() - $cachedAt) < $cacheTtl;
    }

    /**
     * Store license validation in session
     *
     * @param Request $request
     * @param string $licenseKey
     * @param array $result
     * @return void
     */
    protected function storeLicenseInSession(Request $request, string $licenseKey, array $result): void
    {
        $request->session()->put($this->getSessionKey(), [
            'license_key' => $licenseKey,
            'result' => $result,
            'cached_at' => time(),
        ]);
    }

    /**
     * Handle invalid license
     *
     * @param Request $request
     * @param string $message
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    protected function handleInvalidLicense(Request $request, string $message)
    {
        // Clear session cache
        if ($this->isSessionCachingEnabled()) {
            $request->session()->forget($this->getSessionKey());
        }

        // Handle JSON requests
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 403);
        }

        // Redirect with error message
        $redirectTo = config('getkeymanager.middleware.redirect_to', '/license-required');

        return redirect($redirectTo)->with('error', $message);
    }
}
