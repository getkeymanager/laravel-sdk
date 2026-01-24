<?php

declare(strict_types=1);

namespace GetKeyManager\Laravel\Commands;

use Illuminate\Console\Command;
use GetKeyManager\Laravel\Facades\GetKeyManager;
use Exception;

/**
 * License Deactivate Command
 * 
 * Artisan command to deactivate a license key from the CLI.
 */
class LicenseDeactivateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'license:deactivate
                            {license_key : The license key to deactivate}
                            {--hardware-id= : Hardware ID to deactivate}
                            {--domain= : Domain to deactivate}
                            {--activation-id= : Specific activation UUID to deactivate}
                            {--all : Deactivate all activations}
                            {--json : Output result as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deactivate a license key from a device or domain';

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
            'activationId' => $this->option('activation-id'),
        ]);

        if ($this->option('all')) {
            if (!$this->confirm('This will deactivate ALL activations for this license. Continue?')) {
                $this->info('Deactivation cancelled.');
                return 0;
            }
            $options['all'] = true;
        }

        if (empty($options)) {
            $this->error('Please specify --hardware-id, --domain, --activation-id, or --all');
            return 1;
        }

        try {
            $this->info("Deactivating license key: {$licenseKey}");
            
            if (!empty($options)) {
                $this->line("Options: " . json_encode($options));
            }

            $result = GetKeyManager::deactivateLicense($licenseKey, $options);

            if ($this->option('json')) {
                $this->line(json_encode($result, JSON_PRETTY_PRINT));
                return 0;
            }

            if ($result['success'] ?? false) {
                $this->info('✓ License deactivated successfully');
                
                if (isset($result['data']['deactivated_count'])) {
                    $count = $result['data']['deactivated_count'];
                    $this->line("Deactivated {$count} activation(s)");
                }

                return 0;
            } else {
                $this->error('✗ License deactivation failed');
                $this->line("Message: " . ($result['message'] ?? 'Unknown error'));
                return 1;
            }
        } catch (Exception $e) {
            $this->error('✗ Deactivation error: ' . $e->getMessage());
            
            if ($this->option('verbose')) {
                $this->line($e->getTraceAsString());
            }

            return 1;
        }
    }
}
