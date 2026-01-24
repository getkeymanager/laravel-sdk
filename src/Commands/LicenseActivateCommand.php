<?php

declare(strict_types=1);

namespace LicenseManager\Laravel\Commands;

use Illuminate\Console\Command;
use LicenseManager\Laravel\Facades\LicenseManager;
use Exception;

/**
 * License Activate Command
 * 
 * Artisan command to activate a license key from the CLI.
 */
class LicenseActivateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'license:activate
                            {license_key : The license key to activate}
                            {--hardware-id= : Hardware ID for activation (auto-generated if not provided)}
                            {--domain= : Domain for activation}
                            {--name= : Activation name/label}
                            {--json : Output result as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Activate a license key on this device or domain';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $licenseKey = $this->argument('license_key');
        
        $hardwareId = $this->option('hardware-id');
        $domain = $this->option('domain');

        // Generate hardware ID if not provided and no domain
        if (!$hardwareId && !$domain) {
            $hardwareId = LicenseManager::generateHardwareId();
            $this->info("Generated Hardware ID: {$hardwareId}");
        }

        if (!$hardwareId && !$domain) {
            $this->error('Either --hardware-id or --domain is required');
            return 1;
        }

        $options = array_filter([
            'hardwareId' => $hardwareId,
            'domain' => $domain,
            'name' => $this->option('name'),
        ]);

        try {
            $this->info("Activating license key: {$licenseKey}");
            
            if (!empty($options)) {
                $this->line("Options: " . json_encode($options));
            }

            $result = LicenseManager::activateLicense($licenseKey, $options);

            if ($this->option('json')) {
                $this->line(json_encode($result, JSON_PRETTY_PRINT));
                return 0;
            }

            if ($result['success'] ?? false) {
                $this->info('✓ License activated successfully');
                
                if (isset($result['data']['activation'])) {
                    $this->displayActivationInfo($result['data']['activation']);
                }

                return 0;
            } else {
                $this->error('✗ License activation failed');
                $this->line("Message: " . ($result['message'] ?? 'Unknown error'));
                return 1;
            }
        } catch (Exception $e) {
            $this->error('✗ Activation error: ' . $e->getMessage());
            
            if ($this->option('verbose')) {
                $this->line($e->getTraceAsString());
            }

            return 1;
        }
    }

    /**
     * Display activation information
     *
     * @param array $activation
     * @return void
     */
    protected function displayActivationInfo(array $activation): void
    {
        $this->newLine();
        $this->line('<fg=yellow>Activation Information:</>');
        $this->table(
            ['Property', 'Value'],
            [
                ['Activation ID', $activation['uuid'] ?? 'N/A'],
                ['Hardware ID', $activation['hardware_id'] ?? 'N/A'],
                ['Domain', $activation['domain'] ?? 'N/A'],
                ['Name', $activation['name'] ?? 'N/A'],
                ['Status', $activation['status'] ?? 'N/A'],
                ['Activated At', $activation['activated_at'] ?? 'N/A'],
            ]
        );
    }
}
