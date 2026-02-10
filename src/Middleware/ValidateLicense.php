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
 * Validate License Middleware (Hardened)
 * 
 * Protects routes by validating a license key using the hardened LicenseState API.
 * 
 * Features:
 * - Uses LicenseState for robust validation
 * - Supports grace period for network failures
 * - Multiple enforcement layers
 * - API response code integration
 * 
 * The license key can be provided via:
 * - Query parameter: ?license_key=XXXXX
 * - Request body: license_key field
 * - Session: stored from previous validation
 * - Header: X-License-Key
 * 
 * Version 2.0 - Hardened with LicenseState integration
 */
class ValidateLicense
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $productId  Optional product ID to validate against
     * @param  bool  $allowGrace  Allow grace period (default: true)
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ?string $productId = null, bool $allowGrace = true)
    {
        // Check for Kill Switch status (Anti-Piracy)
        if (GetKeyManager::isKilled()) {
            return $this->handleKilledApplication($request);
        }

        // Get license key from various sources
        $licenseKey = $this->getLicenseKey($request);

        if (!$licenseKey) {
            return $this->handleInvalidLicense($request, 'License key is required', null);
        }

        // Check session cache first
        if ($this->isSessionCachingEnabled() && $this->isLicenseValidInSession($request, $licenseKey)) {
            return $next($request);
        }

        try {
            // Resolve license state (hardened validation)
            $options = [];
            if ($productId) {
                $options['productId'] = $productId;
            }

            $licenseState = GetKeyManager::resolveLicenseState($licenseKey, $options);

            // Check if license is valid (active or grace)
            if (!$licenseState->isValid()) {
                return $this->handleInvalidLicense(
                    $request,
                    'License is not valid: ' . $licenseState->getState(),
                    $licenseState
                );
            }

            // Optional: Reject grace period if strict validation required
            if (!$allowGrace && $licenseState->isInGracePeriod()) {
                return $this->handleInvalidLicense(
                    $request,
                    'License is in grace period. Please renew your license.',
                    $licenseState
                );
            }

            // Check if license allows access
            if (!$licenseState->allows('access')) {
                return $this->handleInvalidLicense(
                    $request,
                    'License does not allow access to this resource',
                    $licenseState
                );
            }

            // Store in session if caching is enabled
            if ($this->isSessionCachingEnabled()) {
                $this->storeLicenseInSession($request, $licenseKey, $licenseState);
            }

            // Attach license state to request for use in controllers
            $request->merge([
                '_license_state' => $licenseState,
                '_license_key' => $licenseKey,
            ]);

            return $next($request);
        } catch (LicenseException $e) {
            return $this->handleLicenseException($request, $e);
        } catch (Exception $e) {
            return $this->handleInvalidLicense($request, $e->getMessage(), null);
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
     * @param mixed $licenseState
     * @return void
     */
    protected function storeLicenseInSession(Request $request, string $licenseKey, $licenseState): void
    {
        $request->session()->put($this->getSessionKey(), [
            'license_key' => $licenseKey,
            'state' => $licenseState->getState(),
            'is_valid' => $licenseState->isValid(),
            'cached_at' => time(),
        ]);
    }

    /**
     * Handle license exception with API response codes
     *
     * @param Request $request
     * @param LicenseException $exception
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    protected function handleLicenseException(Request $request, LicenseException $exception)
    {
        $apiCode = $exception->getApiCode();
        $message = $exception->getMessage();

        // Handle specific API response codes
        $userMessage = match($apiCode) {
            ApiResponseCode::LICENSE_EXPIRED => 'Your license has expired. Please renew to continue.',
            ApiResponseCode::LICENSE_BLOCKED => 'Your license has been blocked. Please contact support.',
            ApiResponseCode::ACTIVATION_LIMIT_REACHED => 'License activation limit reached.',
            ApiResponseCode::INVALID_LICENSE_KEY => 'Invalid license key provided.',
            default => $message,
        };

        return $this->handleInvalidLicense($request, $userMessage, null, $apiCode);
    }

    /**
     * Handle invalid license
     *
     * @param Request $request
     * @param string $message
     * @param mixed|null $licenseState
     * @param int|null $apiCode
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    protected function handleInvalidLicense(Request $request, string $message, $licenseState = null, ?int $apiCode = null)
    {
        // Clear session cache
        if ($this->isSessionCachingEnabled()) {
            $request->session()->forget($this->getSessionKey());
        }

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

            if ($licenseState !== null) {
                $response['state'] = $licenseState->getState();
            }

            return response()->json($response, 403);
        }

        // Redirect with error message
        $redirectTo = config('getkeymanager.middleware.redirect_to', '/license-required');

        return redirect($redirectTo)
            ->with('error', $message)
            ->with('api_code', $apiCode);
    }

    /**
     * Handle requests for a killed application (Kill Switch)
     */
    protected function handleKilledApplication(Request $request)
    {
        // Handle JSON requests
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized software copy. This installation has been disabled.',
                'action' => 'CONTACT_SUPPORT',
                'support_url' => 'https://getkeymanager.com/support'
            ], 403);
        }

        // Redirect to legal notice/support page
        $redirectUrl = config('getkeymanager.middleware.killed_redirect_url', 'https://getkeymanager.com/legal-notice');
        
        return redirect()->away($redirectUrl);
    }
}
