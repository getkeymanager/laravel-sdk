<?php

declare(strict_types=1);

namespace GetKeyManager\Laravel\Commands;

use Illuminate\Console\Command;
use GetKeyManager\Laravel\Facades\GetKeyManager;
use Exception;

/**
 * License Validate Command
 * 
 * Artisan command to validate a license key from the CLI.
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
                            {--json : Output result as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate a license key';

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

            $result = GetKeyManager::validateLicense($licenseKey, $options);

            if ($this->option('json')) {
                $this->line(json_encode($result, JSON_PRETTY_PRINT));
                return 0;
            }

            if ($result['success'] ?? false) {
                $this->info('✓ License is valid');
                
                if (isset($result['data'])) {
                    $this->displayLicenseInfo($result['data']);
                }

                return 0;
            } else {
                $this->error('✗ License validation failed');
                $this->line("Message: " . ($result['message'] ?? 'Unknown error'));
                return 1;
            }
        } catch (Exception $e) {
            $this->error('✗ Validation error: ' . $e->getMessage());
            
            if ($this->option('verbose')) {
                $this->line($e->getTraceAsString());
            }

            return 1;
        }
    }

    /**
     * Display license information
     *
     * @param array $data
     * @return void
     */
    protected function displayLicenseInfo(array $data): void
    {
        $this->newLine();
        $this->line('<fg=yellow>License Information:</>');
        $this->table(
            ['Property', 'Value'],
            [
                ['License Key', $data['license_key'] ?? 'N/A'],
                ['Status', $data['status'] ?? 'N/A'],
                ['Product', $data['product']['name'] ?? 'N/A'],
                ['Customer', $data['customer_email'] ?? 'Not assigned'],
                ['Activations', ($data['activation_count'] ?? 0) . ' / ' . ($data['activation_limit'] ?? 'unlimited')],
                ['Valid Until', $data['valid_until'] ?? 'No expiration'],
                ['Created At', $data['created_at'] ?? 'N/A'],
            ]
        );

        if (!empty($data['features'])) {
            $this->newLine();
            $this->line('<fg=yellow>Enabled Features:</>');
            foreach ($data['features'] as $feature => $enabled) {
                $icon = $enabled ? '✓' : '✗';
                $color = $enabled ? 'green' : 'red';
                $this->line("<fg={$color}>{$icon}</> {$feature}");
            }
        }
    }
}
