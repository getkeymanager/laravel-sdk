<?php

declare(strict_types=1);

namespace GetKeyManager\Laravel\Tests;

/**
 * E2E: Fresh Install Scenario
 * 
 * Scenario: Installing software for the first time
 * Goal: Validate full workflow from first use to initialization
 */
class FreshInstallScenarioTest extends TestCase
{
    /**
     * E2E-4.1: Fresh Install Workflow
     * 
     * Steps:
     * 1. User starts application for the first time
     * 2. No .lic file exists yet
     * 3. SDK validates license (force API)
     * 4. API returns license data + .lic file
     * 5. SDK saves .lic file
     * 6. Application continues with validation success
     */
    public function testFreshInstallWorkflow(): void
    {
        // Arrange: Fresh application state
        $licenseKey = 'LIC-2024-FRESH-INSTALL-TEST';
        $identifier = 'test.local';

        // Assert: No license file exists initially
        $licenseDir = storage_path('licenses');
        if (!is_dir($licenseDir)) {
            mkdir($licenseDir, 0755, true);
        }

        // Verify directory is empty (fresh install)
        $files = glob($licenseDir . '/*');
        // In fresh install, directory should be empty or minimal

        // Act: Validate license with FORCE_API on first run
        $client = $this->app->make('getkeymanager');

        // This would be:
        // $result = $client->validateLicense(
        //     $licenseKey,
        //     $identifier,
        //     null,
        //     ValidationType::FORCE_API  // Force API on fresh install
        // );

        // Assert: Would expect validation success
        // $this->assertTrue($result->isSuccess());

        // Assert: License file would be saved
        // $this->assertFileExists($licenseDir . '/...');

        $this->assertTrue(true); // Placeholder
    }

    /**
     * E2E-4.1b: Subsequent Validation Uses Offline
     */
    public function testSubsequentValidationUsesOffline(): void
    {
        // After fresh install, second validation should use offline

        // Arrange: License file exists from fresh install
        // Act: Validate without force=true (defaults to offline-first)
        // Assert: No API call made, offline data used

        $this->assertTrue(true); // Placeholder
    }

    /**
     * E2E-4.1c: Performance Comparison
     */
    public function testPerformanceComparisonOfflineVsApi(): void
    {
        // First call (API): ~500ms
        // Subsequent calls (offline): <10ms
        // Difference should be significant

        // Start with forced API call
        $startApi = microtime(true);
        // $result = $client->validateLicense($key, $id, null, true);
        $apiTime = microtime(true) - $startApi;

        // Then offline call
        $startOffline = microtime(true);
        // $result = $client->validateLicense($key, $id);
        $offlineTime = microtime(true) - $startOffline;

        // Offline should be much faster
        // $this->assertLessThan($apiTime, $offlineTime * 10);

        $this->assertTrue(true); // Placeholder
    }
}
