<?php

namespace DarshPhpDev\EdfaPay\Console\Commands;

use Illuminate\Console\Command;
use DarshPhpDev\EdfaPay\Services\EdfaPayClient;
use DarshPhpDev\EdfaPay\Exceptions\EdfaPayApiException;

class EdfaPayCheckCommand extends Command
{
    protected $signature   = 'edfapay:check';
    protected $description = 'Validate EdfaPay configuration and API connectivity';

    const SANDBOX_KEY = 'BF15E34275189913593F283D691E39C5849B514E41C8E7D6ACA8BB99319C08C2';

    public function handle(): void
    {
        $config = config('edfapay');

        $this->writeEnvDefaults();

        // 1. Resolve API key
        if (empty($config['api_key'])) {
            $this->warn('EDFAPAY_API_KEY is not set.');

            $choice = $this->choice('How would you like to proceed?', [
                'Enter my API key',
                'Use sandbox key for testing',
                'Abort',
            ], 2);

            if ($choice === 'Enter my API key') {
                $config['api_key'] = $this->ask('Enter your EdfaPay API key');
                $this->writeEnvValue('EDFAPAY_API_KEY', $config['api_key']);
            } elseif ($choice === 'Use sandbox key for testing') {
                $config['api_key'] = self::SANDBOX_KEY;
                $this->writeEnvValue('EDFAPAY_API_KEY', $config['api_key']);
                $this->warn('Using sandbox API key. Do not use this in production.');
            } else {
                $this->info('Aborted.');
                return;
            }
        }

        $this->info('API Key: ✓');
        $this->info('Mode: ' . $config['mode']);
        $this->info('Currency: ' . $config['currency']);

        // 2. Test connectivity
        $this->info('Testing API connectivity...');

        try {
            $client = new EdfaPayClient($config);
            $client->queryTransaction('00000000-0000-0000-0000-000000000000');
            $this->info('API connectivity: ✓');
        } catch (EdfaPayApiException $e) {
            if ($e->getCode() > 0) {
                $this->info('API connectivity: ✓');
            } else {
                $this->error('API connectivity: ✗ — ' . $e->getMessage());
            }
        }
    }

    /**
     * Write all missing EdfaPay .env keys with their default values.
     */
    protected function writeEnvDefaults(): void
    {
        $defaults = [
            'EDFAPAY_MODE'     => 'demo',
            'EDFAPAY_API_KEY'  => '',
            'EDFAPAY_CURRENCY' => 'SAR',
        ];

        $hasAnyMissing = false;
        foreach ($defaults as $key => $default) {
            if (!$this->envKeyExists($key)) {
                $hasAnyMissing = true;
                break;
            }
        }

        if ($hasAnyMissing) {
            $this->appendToEnv(PHP_EOL . '# EdfaPay Configurations');
        }

        foreach ($defaults as $key => $default) {
            if (!$this->envKeyExists($key)) {
                $this->writeEnvValue($key, $default);
                if ($key !== 'EDFAPAY_API_KEY') {
                    $this->info("Added {$key}={$default} to .env");
                }
            }
        }
    }

    /**
     * Append a raw line to the .env file.
     */
    protected function appendToEnv(string $line): void
    {
        $path = base_path('.env');
        $env  = file_get_contents($path);

        if ($env === false) {
            $this->error('.env file not found.');
            return;
        }

        file_put_contents($path, rtrim($env) . PHP_EOL . $line . PHP_EOL);
    }

    /**
     * Check if a key already exists in the .env file.
     */
    protected function envKeyExists(string $key): bool
    {
        $env = file_get_contents(base_path('.env'));
        return $env !== false && preg_match('/^' . preg_quote($key, '/') . '=/m', $env);
    }

    /**
     * Set or update a key=value pair in the .env file.
     */
    protected function writeEnvValue(string $key, string $value): void
    {
        $path = base_path('.env');
        $env  = file_get_contents($path);

        if ($env === false) {
            $this->error('.env file not found.');
            return;
        }

        $line = $key . '=' . $value;

        if (preg_match('/^' . preg_quote($key, '/') . '=/m', $env)) {
            $env = preg_replace('/^' . preg_quote($key, '/') . '=.*/m', $line, $env);
        } else {
            $env = rtrim($env) . PHP_EOL . $line . PHP_EOL;
        }

        file_put_contents($path, $env);
    }
}
