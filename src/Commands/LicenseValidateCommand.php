<?php

declare(strict_types=1);

namespace GetKeyManager\Laravel\Commands;

use Illuminate\Console\Command;
use GetKeyManager\Laravel\Facades\GetKeyManager;
use GetKeyManager\Laravel\Core\LicenseException;
use GetKeyManager\Laravel\Core\ApiResponseCode;
use Exception;

/**
 * License Validate Command (Hardened)
 * 
 * Artisan command to validate a license key from the CLI using
 * the hardened LicenseState API.
 * 
 * Version 2.0 - Hardened with LicenseState integration
 */
class LicenseValidateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'license:validate
                            {license_key : The license key to validate}
                            {--hardware-id= : Hardware ID to validate against}
                            {--domain= : Domain to validate against}
                            {--product-id= : Product ID to validate against}
                            {--show-state : Show detailed license state information}
                            {--json : Output result as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate a license key using hardened LicenseState API';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $licenseKey = $this->argument('license_key');
        
        $options = array_filter([
            'hardwareId' => $this->option('hardware-id'),
            'domain' => $this->option('domain'),
            'productId' => $this->option('product-id'),
        ]);

        try {
            $this->info("Validating license key: {$licenseKey}");
            
            if (!empty($options)) {
                $this->line("Options: " . json_encode($options));
            }

            // Use hardened resolveLicenseState
            $licenseState = GetKeyManager::resolveLicenseState($licenseKey, $options);

            if ($this->option('json')) {
                $this->line(json_encode([
                    'success' => $licenseState->isValid(),
                    'state' => $licenseState->getState(),
                    'is_active' => $licenseState->isActive(),
                    'is_valid' => $licenseState->isValid(),
                    'is_grace_period' => $licenseState->isInGracePeriod(),
                    'expires_at' => $licenseState->getExpiresAt(),
                ], JSON_PRETTY_PRINT));
                return 0;
            }

            // Display state information
            $this->displayLicenseState($licenseState);

            // Show detailed state if requested
            if ($this->option('show-state')) {
                $this->displayDetailedState($licenseState);
            }

            return $licenseState->isValid() ? 0 : 1;
        } catch (LicenseException $e) {
            return $this->handleLicenseException($e);
        } catch (Exception $e) {
            $this->error('✗ Validation error: ' . $e->getMessage());
            
            if ($this->option('verbose')) {
                $this->line($e->getTraceAsString());
            }

            return 1;
        }
    }

    /**
     * Display license state information
     *
     * @param mixed $licenseState
     * @return void
     */
    protected function displayLicenseState($licenseState): void
    {
        $state = $licenseState->getState();
        $isValid = $licenseState->isValid();
        
        // Determine status icon and color
        [$icon, $color] = match($state) {
            'ACTIVE' => ['✓', 'green'],
            'GRACE' => ['⚠', 'yellow'],
            'RESTRICTED' => ['⊘', 'yellow'],
            'INVALID' => ['✗', 'red'],
            default => ['?', 'gray'],
        };

        $this->newLine();
        $this->line("<fg={$color}>{$icon} License State: {$state}</>");
        $this->line("Valid: " . ($isValid ? 'Yes' : 'No'));
        
        if ($licenseState->isActive()) {
            $this->info('✓ License is ACTIVE');
        } elseif ($licenseState->isInGracePeriod()) {
            $this->warn('⚠ License is in GRACE PERIOD');
            $expiresAt = $licenseState->getExpiresAt();
            if ($expiresAt) {
                $this->line("  Grace expires: " . date('Y-m-d H:i:s', $expiresAt));
            }
        } else {
            $this->error('✗ License is INVALID');
        }
    }

    /**
     * Display detailed state information
     *
     * @param mixed $licenseState
     * @return void
     */
    protected function displayDetailedState($licenseState): void
    {
        $this->newLine();
        $this->line('<fg=yellow>Detailed State Information:</>');
        
        $details = [
            ['Property', 'Value'],
            ['State', $licenseState->getState()],
            ['Is Active', $licenseState->isActive() ? 'Yes' : 'No'],
            ['Is Valid', $licenseState->isValid() ? 'Yes' : 'No'],
            ['In Grace Period', $licenseState->isInGracePeriod() ? 'Yes' : 'No'],
        ];
        
        if ($licenseState->getExpiresAt()) {
            $details[] = ['Expires At', date('Y-m-d H:i:s', $licenseState->getExpiresAt())];
        }
        
        if ($licenseState->getValidFrom()) {
            $details[] = ['Valid From', date('Y-m-d H:i:s', $licenseState->getValidFrom())];
        }
        
        $this->table($details[0], array_slice($details, 1));
        
        // Show capabilities
        $this->newLine();
        $this->line('<fg=yellow>Capabilities:</>');
        $capabilities = ['access', 'download', 'update', 'support'];
        foreach ($capabilities as $capability) {
            $allowed = $licenseState->allows($capability);
            $icon = $allowed ? '✓' : '✗';
            $color = $allowed ? 'green' : 'red';
            $this->line("<fg={$color}>{$icon}</> {$capability}");
        }
    }

    /**
     * Handle license exception
     *
     * @param LicenseException $exception
     * @return int
     */
    protected function handleLicenseException(LicenseException $exception): int
    {
        $apiCode = $exception->getApiCode();
        $apiCodeName = $exception->getApiCodeName();
        
        $this->error('✗ License validation failed');
        $this->line("Error: " . $exception->getMessage());
        
        if ($apiCode !== null) {
            $this->line("API Code: {$apiCode} ({$apiCodeName})");
            
            // Provide specific guidance based on error code
            $guidance = match($apiCode) {
                ApiResponseCode::LICENSE_EXPIRED => 'Please renew your license to continue using this software.',
                ApiResponseCode::LICENSE_BLOCKED => 'Your license has been blocked. Please contact support.',
                ApiResponseCode::ACTIVATION_LIMIT_REACHED => 'Please deactivate an existing installation or upgrade your license.',
                ApiResponseCode::INVALID_LICENSE_KEY => 'Please check your license key and try again.',
                default => null,
            };
            
            if ($guidance) {
                $this->newLine();
                $this->warn("💡 {$guidance}");
            }
        }
        
        return 1;
    }
}
