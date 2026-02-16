<?php

declare(strict_types=1);

namespace GetKeyManager\Laravel\Core;

use GetKeyManager\Laravel\Core\ApiResponseCode;

/**
 * StateResolver - Resolves EntitlementState from API Responses
 * 
 * This class is responsible for transforming API validation responses
 * into EntitlementState objects with proper signature verification.
 * 
 * @internal This class is for internal SDK use only
 * @package GetKeyManager\Laravel\Core
 */
class StateResolver
{
    private ?SignatureVerifier $verifier;
    private ?string $environment;
    private ?string $productId;

    /**
     * Initialize StateResolver
     * 
     * @param SignatureVerifier|null $verifier Signature verifier
     * @param string|null $environment Environment context
     * @param string|null $productId Product ID context
     */
    public function __construct(
        ?SignatureVerifier $verifier = null,
        ?string $environment = null,
        ?string $productId = null
    ) {
        $this->verifier = $verifier;
        $this->environment = $environment;
        $this->productId = $productId;
    }

    /**
     * Resolve EntitlementState from API validation response
     * 
     * @param array $response Full API response
     * @param string|null $licenseKey Associated license key
     * @return LicenseState
     * @throws SignatureException If signature verification fails
     * @throws ValidationException If response format is invalid
     */
    public function resolveFromValidation(array $response, ?string $licenseKey = null): LicenseState
    {
        // Verify signature if available
        $signature = null;
        if (isset($response['signature']) && $this->verifier) {
            $signature = $response['signature'];
            $this->verifySignature($response, $signature);
        }

        // Extract and normalize payload
        $payload = $this->normalizeValidationPayload($response);
        
        // Add context
        $payload['environment'] = $this->environment;
        $payload['product_id'] = $this->productId;
        $payload['issued_at'] = $response['timestamp'] ?? time();

        // Create EntitlementState
        $entitlementState = new EntitlementState($payload, $signature);

        // Wrap in public LicenseState
        return new LicenseState($entitlementState, $licenseKey);
    }

    /**
     * Resolve EntitlementState from feature check response
     * 
     * @param array $response Feature check response
     * @param string|null $licenseKey Associated license key
     * @return LicenseState
     */
    public function resolveFromFeatureCheck(array $response, ?string $licenseKey = null): LicenseState
    {
        $signature = $response['signature'] ?? null;
        if ($signature && $this->verifier) {
            $this->verifySignature($response, $signature);
        }

        $payload = [
            'valid' => $response['enabled'] ?? false,
            'features' => [
                $response['feature'] ?? 'unknown' => $response['value'] ?? $response['enabled'] ?? false,
            ],
            'issued_at' => $response['timestamp'] ?? time(),
            'environment' => $this->environment,
            'product_id' => $this->productId,
        ];

        $entitlementState = new EntitlementState($payload, $signature);
        return new LicenseState($entitlementState, $licenseKey);
    }

    /**
     * Resolve EntitlementState from activation response
     * 
     * @param array $response Activation response
     * @param string|null $licenseKey Associated license key
     * @return LicenseState
     */
    public function resolveFromActivation(array $response, ?string $licenseKey = null): LicenseState
    {
        $signature = $response['signature'] ?? null;
        if ($signature && $this->verifier) {
            $this->verifySignature($response, $signature);
        }

        $payload = [
            'valid' => $response['success'] ?? false,
            'status' => 'active',
            'issued_at' => $response['timestamp'] ?? time(),
            'environment' => $this->environment,
            'product_id' => $this->productId,
        ];

        // Extract activation details
        if (isset($response['activation'])) {
            $payload['context_binding'] = isset($response['activation']['hardware_id']) 
                ? hash('sha256', $response['activation']['hardware_id'])
                : null;
            
            if (isset($response['activation']['activated_at'])) {
                $payload['valid_from'] = strtotime($response['activation']['activated_at']);
            }
        }

        $entitlementState = new EntitlementState($payload, $signature);
        return new LicenseState($entitlementState, $licenseKey);
    }

    /**
     * Create a restricted/invalid state (for errors)
     * 
     * @param string $reason Reason for restriction
     * @param string|null $licenseKey Associated license key
     * @return LicenseState
     */
    public function createRestrictedState(string $reason, ?string $licenseKey = null): LicenseState
    {
        $payload = [
            'valid' => false,
            'status' => 'restricted',
            'metadata' => ['reason' => $reason],
            'issued_at' => time(),
            'environment' => $this->environment,
            'product_id' => $this->productId,
        ];

        $entitlementState = new EntitlementState($payload);
        return new LicenseState($entitlementState, $licenseKey);
    }

    /**
     * Create a grace period state (for revalidation failures)
     * 
     * @param array $lastValidState Last known valid state
     * @param string|null $licenseKey Associated license key
     * @return LicenseState
     */
    public function createGraceState(array $lastValidState, ?string $licenseKey = null): LicenseState
    {
        $payload = array_merge($lastValidState, [
            'revalidation_failed' => true,
            'last_verified_at' => $lastValidState['last_verified_at'] ?? time(),
        ]);

        $signature = $lastValidState['signature'] ?? null;
        $entitlementState = new EntitlementState($payload, $signature);
        
        return new LicenseState($entitlementState, $licenseKey);
    }

    /**
     * Normalize validation payload to standard format
     * 
     * @param array $response API response
     * @return array
     */
    private function normalizeValidationPayload(array $response): array
    {
        $payload = [];

        // Handle response.data structure
        if (isset($response['data'])) {
            $data = $response['data'];
            
            $payload['valid'] = $data['valid'] ?? false;
            
            if (isset($data['license'])) {
                $license = $data['license'];
                
                $payload['status'] = $license['status'] ?? null;
                $payload['features'] = $license['features'] ?? [];
                $payload['metadata'] = $license['metadata'] ?? [];
                
                // Extract validity window
                if (isset($license['expires_at'])) {
                    $payload['valid_until'] = $license['expires_at'] 
                        ? strtotime($license['expires_at']) 
                        : null;
                }

                if (isset($license['activated_at'])) {
                    $payload['valid_from'] = strtotime($license['activated_at']);
                }

                // Extract context binding
                if (isset($license['hardware_id'])) {
                    $payload['context_binding'] = hash('sha256', $license['hardware_id']);
                } elseif (isset($license['domain'])) {
                    $payload['context_binding'] = hash('sha256', $license['domain']);
                }
            }
        } else {
            // Direct response format
            $payload['valid'] = $response['valid'] ?? false;
            
            if (isset($response['license'])) {
                $license = $response['license'];
                $payload['status'] = $license['status'] ?? null;
                $payload['features'] = $license['features'] ?? [];
                $payload['metadata'] = $license['metadata'] ?? [];
                
                if (isset($license['expires_at'])) {
                    $payload['valid_until'] = $license['expires_at'] 
                        ? strtotime($license['expires_at']) 
                        : null;
                }
            }
        }

        return $payload;
    }

    /**
     * Verify response signature
     * 
     * @param array $response Full response
     * @param string $signature Signature to verify
     * @throws SignatureException
     */
    private function verifySignature(array $response, string $signature): void
    {
        if (!$this->verifier) {
            return;
        }

        // Remove signature and metadata from response for verification
        $responseForVerification = $response;
        unset($responseForVerification['signature'], $responseForVerification['signature_metadata']);

        $algorithm = $response['signature_metadata']['algorithm'] ?? null;

        $payload = json_encode($responseForVerification, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        
        if (!$this->verifier->verify($payload, $signature, $algorithm ?? 'RSA-SHA256')) {
            throw new SignatureException('Response signature verification failed');
        }
    }

    /**
     * Throw appropriate exception based on API response code
     * 
     * @param array $response Full API response
     * @throws LicenseException Various exception types based on the response code
     */
    public function throwExceptionForResponse(array $response): void
    {
        $code = $response['response']['code'] ?? $response['code'] ?? 0;
        $message = $response['response']['message'] ?? $response['message'] ?? 'Unknown error';
        $data = $response['response']['data'] ?? $response['data'] ?? [];

        // Map API response code to appropriate exception
        $exception = $this->createExceptionForCode($code, $message, $data);
        
        // Set full response data for debugging
        $exception->setResponseData($response);
        
        throw $exception;
    }

    /**
     * Create appropriate exception for API response code
     * 
     * @param int $code API response code
     * @param string $message Error message
     * @param array $data Response data
     * @return LicenseException
     */
    private function createExceptionForCode(int $code, string $message, array $data): LicenseException
    {
        $codeName = ApiResponseCode::getName($code);
        
        // Create exception based on code category
        switch ($code) {
            // API Key Errors
            case ApiResponseCode::INVALID_API_KEY:
                return new ValidationException(
                    $message,
                    401,
                    ValidationException::ERROR_INVALID_API_KEY,
                    $data,
                    null,
                    $code
                );
                
            case ApiResponseCode::INACTIVE_API_KEY:
            case ApiResponseCode::INSUFFICIENT_PERMISSIONS:
            case ApiResponseCode::IP_NOT_ALLOWED:
            case ApiResponseCode::ACCESS_DENIED:
                return new ValidationException(
                    $message,
                    403,
                    ValidationException::ERROR_VALIDATION_ERROR,
                    $data,
                    null,
                    $code
                );

            // License Validation Errors
            case ApiResponseCode::INVALID_LICENSE_KEY:
                return new ValidationException(
                    $message,
                    400,
                    ValidationException::ERROR_INVALID_LICENSE_KEY,
                    $data,
                    null,
                    $code
                );

            case ApiResponseCode::LICENSE_EXPIRED:
                $exception = new ExpiredException(
                    $message,
                    400,
                    ExpiredException::ERROR_LICENSE_EXPIRED,
                    $data,
                    null,
                    $code
                );
                
                // Set expiration timestamp if available
                if (isset($data['license']['expires_at'])) {
                    $exception->setExpiredAt(strtotime($data['license']['expires_at']));
                }
                
                return $exception;

            case ApiResponseCode::LICENSE_BLOCKED:
                return new SuspendedException(
                    $message,
                    400,
                    SuspendedException::ERROR_LICENSE_SUSPENDED,
                    $data,
                    null,
                    $code
                );

            case ApiResponseCode::LICENSE_NOT_ASSIGNED:
                return new ValidationException(
                    $message,
                    400,
                    ValidationException::ERROR_VALIDATION_ERROR,
                    $data,
                    null,
                    $code
                );

            case ApiResponseCode::NO_ACTIVATION_FOUND:
                return new ActivationException(
                    $message,
                    400,
                    ActivationException::ERROR_NOT_ACTIVATED,
                    $data,
                    null,
                    $code
                );

            case ApiResponseCode::PRODUCT_INACTIVE:
                return new ValidationException(
                    $message,
                    400,
                    ValidationException::ERROR_VALIDATION_ERROR,
                    $data,
                    null,
                    $code
                );

            // Activation Errors
            case ApiResponseCode::ACTIVATION_LIMIT_REACHED:
                return new ActivationException(
                    $message,
                    400,
                    ActivationException::ERROR_ACTIVATION_LIMIT_REACHED,
                    $data,
                    null,
                    $code
                );

            case ApiResponseCode::LICENSE_ALREADY_ACTIVE:
                return new ActivationException(
                    $message,
                    400,
                    ActivationException::ERROR_ALREADY_ACTIVATED,
                    $data,
                    null,
                    $code
                );

            // Product/Resource Not Found
            case ApiResponseCode::PRODUCT_NOT_FOUND:
            case ApiResponseCode::PRODUCT_NOT_FOUND_ASSIGN:
            case ApiResponseCode::LICENSE_KEY_NOT_FOUND:
            case ApiResponseCode::LICENSE_KEY_NOT_FOUND_UPDATE:
            case ApiResponseCode::CONTRACT_NOT_FOUND:
            case ApiResponseCode::CONTRACT_NOT_FOUND_INFO:
                return new ValidationException(
                    $message,
                    404,
                    ValidationException::ERROR_VALIDATION_ERROR,
                    $data,
                    null,
                    $code
                );

            // Data Validation Errors
            case ApiResponseCode::INCORRECT_DATA_PRODUCT_CREATE:
            case ApiResponseCode::INCORRECT_DATA_PRODUCT_UPDATE:
            case ApiResponseCode::INCORRECT_DATA_LICENSE_CREATE:
            case ApiResponseCode::INCORRECT_DATA_LICENSE_UPDATE:
            case ApiResponseCode::INCORRECT_DATA_CONTRACT:
            case ApiResponseCode::META_KEY_REQUIRED_CREATE:
            case ApiResponseCode::META_VALUE_REQUIRED_CREATE:
            case ApiResponseCode::META_KEY_REQUIRED_UPDATE:
            case ApiResponseCode::META_VALUE_REQUIRED_UPDATE:
            case ApiResponseCode::META_KEY_REQUIRED_DELETE:
            case ApiResponseCode::IDENTIFIER_REQUIRED:
                return new ValidationException(
                    $message,
                    400,
                    ValidationException::ERROR_VALIDATION_ERROR,
                    $data,
                    null,
                    $code
                );

            // Insufficient Resources
            case ApiResponseCode::INSUFFICIENT_LICENSE_KEYS:
            case ApiResponseCode::LICENSE_KEYS_LIMIT_REACHED:
            case ApiResponseCode::CANNOT_GENERATE_QUANTITY:
                return new ValidationException(
                    $message,
                    400,
                    ValidationException::ERROR_VALIDATION_ERROR,
                    $data,
                    null,
                    $code
                );

            // File/Download Errors
            case ApiResponseCode::FILE_NOT_EXISTS:
            case ApiResponseCode::NO_PERMISSION_FILE:
                return new ValidationException(
                    $message,
                    404,
                    ValidationException::ERROR_VALIDATION_ERROR,
                    $data,
                    null,
                    $code
                );

            // Default: Generic License Exception
            default:
                return new LicenseException(
                    $message,
                    400,
                    $codeName,
                    $data,
                    null,
                    $code
                );
        }
    }
}
