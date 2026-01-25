<?php

declare(strict_types=1);

namespace GetKeyManager\Laravel\Commands;

use Illuminate\Console\Command;
use GetKeyManager\Laravel\Facades\GetKeyManager;
use GetKeyManager\Laravel\Core\LicenseException;
use GetKeyManager\Laravel\Core\ApiResponseCode;
use Exception;

/**
 * License Check State Command
 * 
 * Artisan command to check the current cached license state
 * without making an API call.
 */
class LicenseCheckStateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'license:check-state
                            {license_key : The license key to check}
                            {--clear : Clear cached state}
                            {--json : Output result as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check cached license state without API call';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $licenseKey = $this->argument('license_key');
        
        // Handle clear option
        if ($this->option('clear')) {
            GetKeyManager::clearLicenseState($licenseKey);
            $this->info("✓ Cached state cleared for license: {$licenseKey}");
            return 0;
        }

        try {
            $this->info("Checking cached state for: {$licenseKey}");

            // Get cached state (no API call)
            $licenseState = GetKeyManager::getLicenseState($licenseKey);

            if ($licenseState === null) {
                $this->warn('No cached state found. Use license:validate to fetch and cache state.');
                return 1;
            }

            if ($this->option('json')) {
                $this->line(json_encode([
                    'cached' => true,
                    'state' => $licenseState->getState(),
                    'is_active' => $licenseState->isActive(),
                    'is_valid' => $licenseState->isValid(),
                    'is_grace_period' => $licenseState->isInGracePeriod(),
                    'expires_at' => $licenseState->getExpiresAt(),
                ], JSON_PRETTY_PRINT));
                return 0;
            }

            $this->displayCachedState($licenseState);

            return 0;
        } catch (Exception $e) {
            $this->error('✗ Error: ' . $e->getMessage());
            
            if ($this->option('verbose')) {
                $this->line($e->getTraceAsString());
            }

            return 1;
        }
    }

    /**
     * Display cached license state
     *
     * @param mixed $licenseState
     * @return void
     */
    protected function displayCachedState($licenseState): void
    {
        $state = $licenseState->getState();
        
        $this->newLine();
        $this->line('<fg=cyan>📦 Cached License State</>');
        $this->newLine();
        
        // State badge
        [$icon, $color] = match($state) {
            'ACTIVE' => ['✓', 'green'],
            'GRACE' => ['⚠', 'yellow'],
            'RESTRICTED' => ['⊘', 'yellow'],
            'INVALID' => ['✗', 'red'],
            default => ['?', 'gray'],
        };

        $this->line("<fg={$color}>{$icon} State: {$state}</>");
        
        // Summary
        $this->table(
            ['Property', 'Value'],
            [
                ['Is Active', $licenseState->isActive() ? 'Yes' : 'No'],
                ['Is Valid', $licenseState->isValid() ? 'Yes' : 'No'],
                ['In Grace Period', $licenseState->isInGracePeriod() ? 'Yes' : 'No'],
                ['Expires At', $licenseState->getExpiresAt() 
                    ? date('Y-m-d H:i:s', $licenseState->getExpiresAt()) 
                    : 'Never'],
                ['Valid From', $licenseState->getValidFrom() 
                    ? date('Y-m-d H:i:s', $licenseState->getValidFrom()) 
                    : 'N/A'],
            ]
        );

        // Capabilities
        $this->newLine();
        $this->line('<fg=yellow>Capabilities:</>');
        $capabilities = ['access', 'download', 'update', 'support'];
        foreach ($capabilities as $capability) {
            $allowed = $licenseState->allows($capability);
            $icon = $allowed ? '✓' : '✗';
            $color = $allowed ? 'green' : 'red';
            $this->line("  <fg={$color}>{$icon}</> {$capability}");
        }

        $this->newLine();
        $this->info('💡 Tip: Use license:validate to refresh from API');
    }
}
