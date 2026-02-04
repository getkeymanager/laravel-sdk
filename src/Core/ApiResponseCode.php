<?php

declare(strict_types=1);

namespace GetKeyManager\Laravel\Core;

/**
 * API Response Code Constants
 * 
 * This class mirrors the server-side ApiResponseCode enum and provides
 * a centralized reference for all API response codes used throughout the SDK.
 * 
 * @package GetKeyManager\Laravel\Core
 */
final class ApiResponseCode
{
    // API Key Related [100-149]
    public const INVALID_API_KEY = 100;
    public const INACTIVE_API_KEY = 101;
    public const INSUFFICIENT_PERMISSIONS = 102;
    public const IP_NOT_ALLOWED = 103;

    // Access Restrictions [150-199]
    public const ACCESS_DENIED = 150;

    // Verify [200-219]
    public const VALID_LICENSE_KEY = 200;
    public const LICENSE_NOT_ASSIGNED = 201;
    public const NO_ACTIVATION_FOUND = 202;
    public const PRODUCT_INACTIVE = 203;
    public const LICENSE_BLOCKED = 204;
    public const LICENSE_EXPIRED = 205;
    public const ENVATO_PURCHASE_CODE_ADDED = 206;
    public const INVALID_LICENSE_KEY = 210;
    public const IDENTIFIER_REQUIRED = 215;

    // Deactivate [400-449]
    public const LICENSE_DEACTIVATED = 400;
    public const LICENSE_ALREADY_INACTIVE = 401;

    // Activate [300-349]
    public const LICENSE_ACTIVATED = 300;
    public const LICENSE_ALREADY_ACTIVE = 301;
    public const ACTIVATION_LIMIT_REACHED = 302;

    // Update License Key Meta [350-399]
    public const META_KEY_REQUIRED_UPDATE = 350;
    public const META_VALUE_REQUIRED_UPDATE = 351;
    public const META_KEY_NOT_EXISTS_UPDATE = 352;
    public const METADATA_UPDATED = 353;

    // Get License Key Details [500-549]
    public const ACTIVE_LICENSE_FOUND = 500;
    public const LICENSE_KEY_NOT_FOUND_DETAILS = 501;
    public const LICENSE_FILE_RETRIEVED = 502;
    public const LICENSE_FILE_GENERATION_FAILED = 503;

    // Delete Product [550-599]
    public const PRODUCT_DELETED = 550;
    public const PRODUCT_NOT_FOUND = 551;

    // Access Downloadables [600-649]
    public const DOWNLOADS = 600;

    // Update Product [650-699]
    public const PRODUCT_UPDATED = 650;
    public const INCORRECT_DATA_PRODUCT_UPDATE = 651;

    // Download [700-749]
    public const FILE_NOT_EXISTS = 700;
    public const NO_PERMISSION_FILE = 701;

    // Get License Keys [730-739]
    public const LICENSE_KEYS_LIST = 730;

    // Get Available License Keys Count [740-749]
    public const PRODUCT_NOT_EXISTS_COUNT = 740;
    public const AVAILABLE_LICENSE_KEYS_COUNT = 741;

    // Create Product [750-799]
    public const PRODUCT_CREATED = 750;
    public const INCORRECT_DATA_PRODUCT_CREATE = 751;

    // Assign License Key [800-809]
    public const LICENSE_KEY_ASSIGNED = 800;
    public const INVALID_OR_ALREADY_ASSIGNED = 801;

    // Random Assign License Keys [802-809]
    public const INSUFFICIENT_LICENSE_KEYS = 802;
    public const LICENSE_KEYS_ASSIGNED = 803;
    public const PRODUCT_NOT_FOUND_ASSIGN = 805;
    public const GENERATOR_NOT_FOUND_ASSIGN = 806;
    public const REQUEST_QUEUED = 807;

    // Get All Products [810-819]
    public const ALL_PRODUCTS = 810;

    // Generate [815-819]
    public const GENERATED_LICENSE_KEYS = 815;

    // Get All Generators [820-824]
    public const ALL_GENERATORS = 820;

    // Create Contract [825-829]
    public const INCORRECT_DATA_CONTRACT = 825;

    // Send Telemetry [830-839]
    public const CORRECT_FORMAT_TELEMETRY_SAVED = 830;
    public const INCORRECT_FORMAT_TELEMETRY_SAVED = 831;

    // Contract CRUD [841-846]
    public const CONTRACT_CREATED = 841;
    public const CONTRACT_UPDATED = 842;
    public const CONTRACT_DELETED = 843;
    public const CONTRACT_NOT_FOUND = 844;
    public const ALL_CONTRACTS = 846;

    // Delete License Key [850-859]
    public const LICENSE_KEY_DELETED = 850;
    public const LICENSE_KEY_NOT_FOUND = 851;

    // Create License Keys [900-909]
    public const LICENSE_KEYS_CREATION_RESULT = 900;
    public const INCORRECT_DATA_LICENSE_CREATE = 901;
    public const NO_LICENSE_KEYS_CREATED = 902;

    // Update License Key [950-959]
    public const LICENSE_KEY_UPDATED = 950;
    public const INCORRECT_DATA_LICENSE_UPDATE = 951;
    public const LICENSE_KEY_NOT_FOUND_UPDATE = 952;

    // Get Telemetry [970-979]
    public const TELEMETRY_DATA_FOUND = 970;

    // Create License Key Meta [450-459]
    public const META_KEY_REQUIRED_CREATE = 450;
    public const META_VALUE_REQUIRED_CREATE = 451;
    public const META_KEY_ALREADY_EXISTS_CREATE = 452;
    public const METADATA_CREATED = 453;

    // Delete License Key Meta [250-259]
    public const META_KEY_REQUIRED_DELETE = 250;
    public const META_KEY_NOT_EXISTS_DELETE = 251;
    public const METADATA_DELETED = 252;

    // Create Product Meta [270-279]
    public const PRODUCT_META_KEY_REQUIRED_CREATE = 270;
    public const PRODUCT_META_VALUE_REQUIRED_CREATE = 271;
    public const PRODUCT_META_KEY_ALREADY_EXISTS = 272;
    public const PRODUCT_META_CREATED = 273;
    public const PRODUCT_NOT_FOUND_META_CREATE = 274;

    // Update Product Meta [370-379]
    public const PRODUCT_META_KEY_REQUIRED_UPDATE = 370;
    public const PRODUCT_META_VALUE_REQUIRED_UPDATE = 371;
    public const PRODUCT_META_KEY_NOT_EXISTS_UPDATE = 372;
    public const PRODUCT_META_UPDATED = 373;
    public const PRODUCT_NOT_FOUND_META_UPDATE = 374;

    // Delete Product Meta [480-489]
    public const PRODUCT_META_KEY_REQUIRED_DELETE = 480;
    public const PRODUCT_META_KEY_NOT_EXISTS_DELETE = 481;
    public const PRODUCT_META_DELETED = 482;
    public const PRODUCT_NOT_FOUND_META_DELETE = 483;

    // Contract API [1200-1599]
    public const CONTRACT_NOT_FOUND_INFO = 1200;
    public const CONTRACT_PRODUCT_NOT_FOUND = 1202;
    public const CONTRACT_GENERATOR_NOT_FOUND = 1203;
    public const CONTRACT_FOUND = 1204;
    public const LICENSE_KEYS_LIMIT_REACHED = 1205;
    public const CANNOT_GENERATE_QUANTITY = 1206;
    public const CONTRACT_LICENSE_KEYS_GENERATED = 1300;
    public const CONTRACT_LICENSE_KEY_DELETED = 1400;
    public const CONTRACT_LICENSE_KEY_NOT_FOUND = 1401;
    public const CONTRACT_LICENSE_KEYS_DELETED = 1500;
    public const CONTRACT_NO_LICENSE_KEYS_FOUND = 1501;

    // Changelog API [1600-1649]
    public const CHANGELOG_RETRIEVED = 1600;
    public const CHANGELOG_DISABLED_GLOBALLY = 1601;
    public const CHANGELOG_DISABLED_FOR_PRODUCT = 1602;

    // Product Information API [630-649]
    public const PRODUCT_FOUND = 631;
    public const PRODUCT_PUBLIC_KEY_FOUND = 632;
    public const PRODUCT_PUBLIC_KEY_NOT_FOUND = 633;

    /**
     * Get the constant name for a code (for debugging/logging)
     * 
     * @param int $code The response code
     * @return string The constant name or 'UNKNOWN'
     */
    public static function getName(int $code): string
    {
        $constants = (new \ReflectionClass(__CLASS__))->getConstants();
        $name = array_search($code, $constants, true);
        return $name !== false ? (string)$name : 'UNKNOWN';
    }

    /**
     * Get human-readable message for a response code
     * 
     * @param int $code The response code
     * @return string The message
     */
    public static function getMessage(int $code): string
    {
        return match($code) {
            self::INVALID_API_KEY => 'Invalid API Key',
            self::INACTIVE_API_KEY => 'Inactive API Key',
            self::INSUFFICIENT_PERMISSIONS => "The used API key doesn't have the required capability to access this endpoint",
            self::IP_NOT_ALLOWED => "API key can't be used from this IP address",
            self::ACCESS_DENIED => 'Access denied',
            self::VALID_LICENSE_KEY => 'Valid license key',
            self::LICENSE_NOT_ASSIGNED => 'This license key is not assigned',
            self::NO_ACTIVATION_FOUND => 'No activation found for this license key/identifier combination',
            self::PRODUCT_INACTIVE => "The product associated with this license key is not active or doesn't exist",
            self::LICENSE_BLOCKED => 'License key blocked',
            self::LICENSE_EXPIRED => 'License key expired',
            self::ENVATO_PURCHASE_CODE_ADDED => 'Envato purchase code added',
            self::INVALID_LICENSE_KEY => 'Invalid license key',
            self::IDENTIFIER_REQUIRED => 'Identifier is required',
            self::LICENSE_ACTIVATED => 'License key activated',
            self::LICENSE_ALREADY_ACTIVE => 'License key already active',
            self::ACTIVATION_LIMIT_REACHED => 'License key reached activation limit',
            self::LICENSE_DEACTIVATED => 'License key deactivated',
            self::LICENSE_ALREADY_INACTIVE => 'License key already inactive',
            self::ACTIVE_LICENSE_FOUND => 'Active license key found',
            self::DOWNLOADS => 'Downloads',
            self::FILE_NOT_EXISTS => "The request file doesn't exist",
            self::NO_PERMISSION_FILE => "You don't have permission to access it",
            self::LICENSE_KEY_ASSIGNED => 'License key assigned',
            self::INVALID_OR_ALREADY_ASSIGNED => 'Invalid or already assigned license key',
            self::INSUFFICIENT_LICENSE_KEYS => 'Insufficient license keys',
            self::LICENSE_KEYS_ASSIGNED => 'License keys assigned',
            self::PRODUCT_NOT_FOUND_ASSIGN => 'Product not found',
            self::GENERATOR_NOT_FOUND_ASSIGN => 'Generator not found',
            self::REQUEST_QUEUED => 'Request Queued',
            self::LICENSE_KEYS_CREATION_RESULT => 'License keys creation result',
            self::INCORRECT_DATA_LICENSE_CREATE => 'Incorrect data found',
            self::NO_LICENSE_KEYS_CREATED => 'No license keys created',
            self::LICENSE_KEY_UPDATED => 'License keys updated',
            self::INCORRECT_DATA_LICENSE_UPDATE => 'Incorrect data found',
            self::LICENSE_KEY_NOT_FOUND_UPDATE => 'License key not found',
            self::LICENSE_KEY_DELETED => 'License key deleted',
            self::LICENSE_KEY_NOT_FOUND => 'License key not found',
            self::PRODUCT_CREATED => 'Product created',
            self::INCORRECT_DATA_PRODUCT_CREATE => 'Incorrect data found',
            self::PRODUCT_UPDATED => 'Product updated',
            self::INCORRECT_DATA_PRODUCT_UPDATE => 'Incorrect data found',
            self::PRODUCT_DELETED => 'Product deleted',
            self::PRODUCT_NOT_FOUND => 'Product not found',
            self::META_KEY_REQUIRED_CREATE => 'Meta key is required',
            self::META_VALUE_REQUIRED_CREATE => 'Meta value is required',
            self::META_KEY_ALREADY_EXISTS_CREATE => 'Meta key already exists',
            self::METADATA_CREATED => 'Metadata created',
            self::META_KEY_REQUIRED_UPDATE => 'Meta key is required',
            self::META_VALUE_REQUIRED_UPDATE => 'Meta value is required',
            self::META_KEY_NOT_EXISTS_UPDATE => "Meta key doesn't exist",
            self::METADATA_UPDATED => 'Metadata updated',
            self::META_KEY_REQUIRED_DELETE => 'Meta key is required',
            self::META_KEY_NOT_EXISTS_DELETE => "Meta key doesn't exist",
            self::METADATA_DELETED => 'Metadata deleted',
            self::PRODUCT_META_KEY_REQUIRED_CREATE => 'Meta key is required',
            self::PRODUCT_META_VALUE_REQUIRED_CREATE => 'Meta value is required',
            self::PRODUCT_META_KEY_ALREADY_EXISTS => 'Meta key already exists',
            self::PRODUCT_META_CREATED => 'Product meta created',
            self::PRODUCT_NOT_FOUND_META_CREATE => 'Product not found',
            self::PRODUCT_META_KEY_REQUIRED_UPDATE => 'Meta key is required',
            self::PRODUCT_META_VALUE_REQUIRED_UPDATE => 'Meta value is required',
            self::PRODUCT_META_KEY_NOT_EXISTS_UPDATE => "Meta key doesn't exist",
            self::PRODUCT_META_UPDATED => 'Product meta updated',
            self::PRODUCT_NOT_FOUND_META_UPDATE => 'Product not found',
            self::PRODUCT_META_KEY_REQUIRED_DELETE => 'Meta key is required',
            self::PRODUCT_META_KEY_NOT_EXISTS_DELETE => "Meta key doesn't exist",
            self::PRODUCT_META_DELETED => 'Product meta deleted',
            self::PRODUCT_NOT_FOUND_META_DELETE => 'Product not found',
            self::ALL_PRODUCTS => 'All products',
            self::ALL_GENERATORS => 'All generators',
            self::GENERATED_LICENSE_KEYS => 'Generated license keys',
            self::LICENSE_KEYS_LIST => 'License keys',
            self::CORRECT_FORMAT_TELEMETRY_SAVED => 'Correct format data saved',
            self::INCORRECT_FORMAT_TELEMETRY_SAVED => 'Incorrect format data saved',
            self::PRODUCT_NOT_EXISTS_COUNT => "Product doesn't exist",
            self::AVAILABLE_LICENSE_KEYS_COUNT => 'Available license keys count',
            self::INCORRECT_DATA_CONTRACT => 'Incorrect data found',
            self::CONTRACT_CREATED => 'Contract created',
            self::CONTRACT_UPDATED => 'Contract updated',
            self::CONTRACT_DELETED => 'Contract deleted',
            self::CONTRACT_NOT_FOUND => 'Contract not found',
            self::ALL_CONTRACTS => 'All contracts',
            self::CONTRACT_NOT_FOUND_INFO => 'Contract not found',
            self::CONTRACT_PRODUCT_NOT_FOUND => 'Product not found',
            self::CONTRACT_GENERATOR_NOT_FOUND => 'Generator not found',
            self::CONTRACT_FOUND => 'Contract found',
            self::LICENSE_KEYS_LIMIT_REACHED => 'License keys limit reached',
            self::CANNOT_GENERATE_QUANTITY => 'Cannot generate this quantity',
            self::CONTRACT_LICENSE_KEYS_GENERATED => 'Contract license keys generated',
            self::CONTRACT_LICENSE_KEY_DELETED => 'License key deleted',
            self::CONTRACT_LICENSE_KEY_NOT_FOUND => 'License key not found',
            self::CONTRACT_LICENSE_KEYS_DELETED => 'License keys deleted',
            self::CONTRACT_NO_LICENSE_KEYS_FOUND => 'No license keys found',
            self::CHANGELOG_RETRIEVED => 'Changelog retrieved successfully',
            self::CHANGELOG_DISABLED_GLOBALLY => 'Changelog feature is disabled globally',
            self::CHANGELOG_DISABLED_FOR_PRODUCT => 'Changelog not enabled for this product',
            self::PRODUCT_FOUND => 'Product information retrieved successfully',
            self::PRODUCT_PUBLIC_KEY_FOUND => 'Public key retrieved successfully',
            self::PRODUCT_PUBLIC_KEY_NOT_FOUND => 'Public key not available for product',
            default => 'Unknown response code',
        };
    }

    /**
     * Check if a code represents a success response
     * 
     * @param int $code The response code
     * @return bool True if success code
     */
    public static function isSuccess(int $code): bool
    {
        return in_array($code, [
            self::VALID_LICENSE_KEY,
            self::LICENSE_ACTIVATED,
            self::LICENSE_DEACTIVATED,
            self::ACTIVE_LICENSE_FOUND,
            self::DOWNLOADS,
            self::LICENSE_KEY_ASSIGNED,
            self::LICENSE_KEYS_ASSIGNED,
            self::REQUEST_QUEUED,
            self::ALL_PRODUCTS,
            self::GENERATED_LICENSE_KEYS,
            self::ALL_GENERATORS,
            self::CORRECT_FORMAT_TELEMETRY_SAVED,
            self::LICENSE_KEYS_CREATION_RESULT,
            self::LICENSE_KEY_UPDATED,
            self::LICENSE_KEY_DELETED,
            self::PRODUCT_CREATED,
            self::PRODUCT_UPDATED,
            self::PRODUCT_DELETED,
            self::METADATA_CREATED,
            self::METADATA_UPDATED,
            self::METADATA_DELETED,
            self::PRODUCT_META_CREATED,
            self::PRODUCT_META_UPDATED,
            self::PRODUCT_META_DELETED,
            self::LICENSE_KEYS_LIST,
            self::AVAILABLE_LICENSE_KEYS_COUNT,
            self::CONTRACT_CREATED,
            self::CONTRACT_UPDATED,
            self::CONTRACT_DELETED,
            self::ALL_CONTRACTS,
            self::CONTRACT_FOUND,
            self::CONTRACT_LICENSE_KEYS_GENERATED,
            self::CONTRACT_LICENSE_KEY_DELETED,
            self::CONTRACT_LICENSE_KEYS_DELETED,
            self::TELEMETRY_DATA_FOUND,
            self::CHANGELOG_RETRIEVED,
            self::PRODUCT_FOUND,
            self::PRODUCT_PUBLIC_KEY_FOUND,
        ], true);
    }

    /**
     * Check if a code represents an error response
     * 
     * @param int $code The response code
     * @return bool True if error code
     */
    public static function isError(int $code): bool
    {
        return !self::isSuccess($code);
    }
}
