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
    private const ALGORITHM = OPENSSL_ALGO_SHA256;
    private const MIN_KEY_SIZE = 2048;
    private const EXPECTED_KEY_SIZE = 4096;

    private $publicKey;
    private array $keyDetails;

    /**
     * Initialize signature verifier
     * 
     * @param string $publicKeyPem PEM-encoded RSA public key
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

        $this->keyDetails = openssl_pkey_get_details($this->publicKey);

        if ($this->keyDetails === false) {
            throw new RuntimeException('Failed to get key details');
        }

        $this->validateKeyDetails();
    }

    /**
     * Verify a signature
     * 
     * @param string $data The data that was signed
     * @param string $signature Base64-encoded signature
     * @return bool True if signature is valid
     * @throws RuntimeException If verification fails
     */
    public function verify(string $data, string $signature): bool
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

        $result = openssl_verify($data, $binarySignature, $this->publicKey, self::ALGORITHM);

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
        unset($data['signature']);

        $canonicalJson = $this->canonicalizeJson($data);

        return $this->verify($canonicalJson, $signature);
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
            'type' => $this->keyDetails['type'] ?? null,
            'bits' => $this->keyDetails['bits'] ?? null,
            'key_type' => $this->keyDetails['rsa']['n'] ? 'RSA' : 'Unknown',
        ];
    }

    /**
     * Validate key meets security requirements
     * 
     * @throws InvalidArgumentException If key doesn't meet requirements
     */
    private function validateKeyDetails(): void
    {
        if (!isset($this->keyDetails['type']) || $this->keyDetails['type'] !== OPENSSL_KEYTYPE_RSA) {
            throw new InvalidArgumentException('Key must be RSA type');
        }

        $bits = $this->keyDetails['bits'] ?? 0;

        if ($bits < self::MIN_KEY_SIZE) {
            throw new InvalidArgumentException(
                "Key size must be at least " . self::MIN_KEY_SIZE . " bits (got {$bits})"
            );
        }

        if ($bits < self::EXPECTED_KEY_SIZE) {
            trigger_error(
                "Warning: Key size is {$bits} bits. Expected " . self::EXPECTED_KEY_SIZE . " bits.",
                E_USER_WARNING
            );
        }
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
