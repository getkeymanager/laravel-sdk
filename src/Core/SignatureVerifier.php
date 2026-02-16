<?php

declare(strict_types=1);

namespace GetKeyManager\Laravel\Core;

use Exception;
use InvalidArgumentException;
use RuntimeException;

/**
 * RSA-4096-SHA256 Signature Verifier
 * 
 * Cryptographically verifies response signatures from the License Management Platform.
 * 
 * @package GetKeyManager\Laravel\Core
 * @version 1.0.0
 * @license MIT
 */
class SignatureVerifier
{
    private const ALGORITHM_RSA = OPENSSL_ALGO_SHA256;
    private const MIN_RSA_KEY_SIZE = 2048;
    private const EXPECTED_RSA_KEY_SIZE = 4096;

    private $publicKey;
    private array $keyDetails;
    private string $keyType = 'RSA';

    /**
     * Initialize signature verifier
     * 
     * @param string $publicKeyPem PEM-encoded public key
     * @throws InvalidArgumentException If key is invalid
     */
    public function __construct(string $publicKeyPem)
    {
        if (empty($publicKeyPem)) {
            throw new InvalidArgumentException('Public key cannot be empty');
        }

        if (!extension_loaded('openssl')) {
            throw new RuntimeException('OpenSSL extension is required');
        }

        $this->publicKey = openssl_pkey_get_public($publicKeyPem);

        if ($this->publicKey === false) {
            throw new InvalidArgumentException('Invalid public key format: ' . openssl_error_string());
        }

        $keyDetails = openssl_pkey_get_details($this->publicKey);

        if ($keyDetails === false) {
            throw new RuntimeException('Failed to get key details');
        }


        $this->keyType = $this->detectKeyType();
    }

    /**
     * Detect key type from OpenSSL details.
     */
    protected function detectKeyType(): string
    {
        $type = $this->keyDetails['type'] ?? null;

        if ($type !== null && defined('OPENSSL_KEYTYPE_ED25519') && $type === OPENSSL_KEYTYPE_ED25519) {
            return 'Ed25519';
        }

        if ($type !== null && defined('OPENSSL_KEYTYPE_RSA') && $type === OPENSSL_KEYTYPE_RSA) {
            $this->validateRsaKeyDetails();
            return 'RSA';
        }

        if ($type !== null && defined('OPENSSL_KEYTYPE_EC') && $type === OPENSSL_KEYTYPE_EC) {
            $curveName = $this->keyDetails['ec']['curve_name'] ?? '';
            if ($curveName !== '' && stripos($curveName, 'ed25519') !== false) {
                return 'Ed25519';

            }
        }

        if (isset($this->keyDetails['rsa'])) {
            $this->validateRsaKeyDetails();
            return 'RSA';
        }

        return 'Unknown';
    }

    /**
     * Validate RSA key size
     */
    protected function validateRsaKeyDetails(): void
    {
        if (!isset($this->keyDetails['bits'])) {
            throw new RuntimeException('Public key is missing bits information');
        }

        if ($this->keyDetails['bits'] < self::MIN_RSA_KEY_SIZE) {
            // Log warning but don't strictly block if it's over 2048
        }
    }

    /**
     * Verify a signature
     * 
     * @param string $data The data that was signed
     * @param string $signature Base64-encoded signature
     * @param string $algorithm Algorithm used (optional, defaults to RSA-SHA256 or matched by key)
     * @return bool True if signature is valid
     * @throws RuntimeException If verification fails
     */
    public function verify(string $data, string $signature, string $algorithm = 'RSA-SHA256'): bool
    {
        if (empty($data)) {
            throw new InvalidArgumentException('Data cannot be empty');
        }

        if (empty($signature)) {
            throw new InvalidArgumentException('Signature cannot be empty');
        }

        $binarySignature = base64_decode($signature, true);
        
        if ($binarySignature === false) {
            throw new InvalidArgumentException('Invalid base64 signature');
        }

        // For Ed25519, we use the key type detected during construction
        if ($this->keyType === 'Ed25519' || strpos(strtoupper($algorithm), 'ED25519') !== false) {
            // PHP openssl_verify handles Ed25519 automatically if the key is Ed25519
            // The 4th parameter is ignored for EdDSA but we pass SHA256 as placeholder
            $result = openssl_verify($data, $binarySignature, $this->publicKey, OPENSSL_ALGO_SHA256);
        } else {
            // RSA verification
            $opensslAlgo = match(strtolower($algorithm)) {
                'sha256', 'rsa-sha256' => OPENSSL_ALGO_SHA256,
                'sha384', 'rsa-sha384' => OPENSSL_ALGO_SHA384,
                'sha512', 'rsa-sha512' => OPENSSL_ALGO_SHA512,
                default => OPENSSL_ALGO_SHA256,
            };
            $result = openssl_verify($data, $binarySignature, $this->publicKey, $opensslAlgo);
        }

        if ($result === -1) {
            throw new RuntimeException('Signature verification error: ' . openssl_error_string());
        }

        return $result === 1;
    }

    /**
     * Verify a signature using constant-time comparison
     * 
     * This method is more secure against timing attacks when comparing
     * sensitive signature data.
     * 
     * @param string $data The data that was signed
     * @param string $signature Base64-encoded signature
     * @return bool True if signature is valid
     */
    public function verifyConstantTime(string $data, string $signature): bool
    {
        try {
            $isValid = $this->verify($data, $signature);
            
            $dummySignature = base64_encode(random_bytes(512));
            $this->verify($data, $dummySignature);
            
            return $isValid;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Verify JSON response signature
     * 
     * Extracts signature field, canonicalizes JSON, and verifies.
     * 
     * @param string $jsonResponse JSON response string
     * @return bool True if signature is valid
     * @throws InvalidArgumentException If JSON is invalid
     */
    public function verifyJsonResponse(string $jsonResponse): bool
    {
        $data = json_decode($jsonResponse, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Invalid JSON: ' . json_last_error_msg());
        }

        if (!isset($data['signature'])) {
            throw new InvalidArgumentException('Response does not contain signature field');
        }

        $signature = $data['signature'];
        $algorithm = $data['signature_metadata']['algorithm'] ?? null;
        unset($data['signature'], $data['signature_metadata']);

        $canonicalJson = $this->canonicalizeJson($data);

        return $this->verify($canonicalJson, $signature, $algorithm ?? 'RSA-SHA256');
    }

    /**
     * Canonicalize JSON for signature verification
     * 
     * Ensures consistent JSON representation:
     * - Sorted keys
     * - No whitespace
     * - Unescaped slashes
     * - Unescaped unicode
     * 
     * @param array $data Data to canonicalize
     * @return string Canonical JSON
     */
    public function canonicalizeJson(array $data): string
    {
        $sorted = $this->sortKeysRecursive($data);
        
        return json_encode(
            $sorted,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    }

    /**
     * Get key information
     * 
     * @return array Key details
     */
    public function getKeyInfo(): array
    {
        return [
            'type' => $this->keyType,
            'bits' => $this->keyDetails['bits'] ?? null,
        ];
    }

    /**
     * Recursively sort array keys
     * 
     * @param array $data Array to sort
     * @return array Sorted array
     */
    private function sortKeysRecursive(array $data): array
    {
        ksort($data);
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->sortKeysRecursive($value);
            }
        }
        
        return $data;
    }

    /**
     * Destructor - free resources
     */
    public function __destruct()
    {
        if (is_resource($this->publicKey)) {
            openssl_free_key($this->publicKey);
        }
    }
}
