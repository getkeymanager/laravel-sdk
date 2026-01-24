<?php

declare(strict_types=1);

namespace LicenseManager\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * License Manager Facade
 * 
 * @method static array validateLicense(string $licenseKey, array $options = [])
 * @method static array activateLicense(string $licenseKey, array $options = [])
 * @method static array deactivateLicense(string $licenseKey, array $options = [])
 * @method static array checkFeature(string $licenseKey, string $featureName)
 * @method static array validateOfflineLicense(string|array $offlineLicenseData, array $options = [])
 * @method static array getLicenseDetails(string $licenseKey)
 * @method static array getLicenseActivations(string $licenseKey)
 * @method static array createLicenseKeys(string $productUuid, string $generatorUuid, array $licenses, ?string $customerEmail = null, array $options = [])
 * @method static array updateLicenseKey(string $licenseKey, array $options = [])
 * @method static array suspendLicense(string $licenseKey)
 * @method static array resumeLicense(string $licenseKey)
 * @method static array revokeLicense(string $licenseKey)
 * @method static array assignLicense(string $licenseKey, string $customerEmail)
 * @method static array unassignLicense(string $licenseKey)
 * @method static array assignRandomLicense(string $productUuid, string $generatorUuid, string $customerEmail, array $options = [])
 * @method static array getLicenseMetadata(string $licenseKey)
 * @method static array updateLicenseMetadata(string $licenseKey, array $metadata)
 * @method static array deleteLicenseMetadata(string $licenseKey, string $key)
 * @method static array getProducts(array $options = [])
 * @method static array getProductByUuid(string $productUuid)
 * @method static array getProductMetadata(string $productUuid)
 * @method static array getGenerators(string $productUuid)
 * @method static array getGeneratorByUuid(string $generatorUuid)
 * @method static array getContracts(array $options = [])
 * @method static array getContractByUuid(string $contractUuid)
 * @method static array validateContract(string $contractCode, array $options = [])
 * @method static array consumeContract(string $contractCode, array $options = [])
 * @method static array getDownloadables(string $productUuid, array $options = [])
 * @method static array getDownloadableByUuid(string $downloadableUuid)
 * @method static array getDownloadUrl(string $downloadableUuid, ?string $licenseKey = null)
 * @method static array sendTelemetry(string $licenseKey, array $telemetryData)
 * @method static array getChangelog(string $productUuid, array $options = [])
 * @method static array getChangelogEntry(string $entryUuid)
 * @method static string generateHardwareId()
 * @method static string generateUuid()
 * @method static \LicenseManager\SDK\LicenseClient getClient()
 *
 * @see \LicenseManager\Laravel\LicenseManagerClient
 */
class LicenseManager extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'licensemanager';
    }
}
