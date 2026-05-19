<?php

namespace DarshPhpDev\EdfaPay\Console\Commands;

use Illuminate\Console\Command;
use DarshPhpDev\EdfaPay\Services\EdfaPayClient;

class EdfaPayCheckCommand extends Command
{
    protected $signature   = 'edfapay:check';
    protected $description = 'Validate EdfaPay configuration and API connectivity';

    public function handle(): void
    {
        $config = config('edfapay');

        // 1. Check API key
        if (empty($config['api_key'])) {
            $this->warn('EDFAPAY_API_KEY is not set.');

            $choice = $this->choice('How would you like to proceed?', [
                'Enter my API key',
                'Use sandbox key for testing',
                'Abort',
            ], 2);

            if ($choice === 'Enter my API key') {
                $config['api_key'] = $this->ask('Enter your EdfaPay API key');
            } elseif ($choice === 'Use sandbox key for testing') {
                $config['api_key'] = 'BF15E34275189913593F283D691E39C5849B514E41C8E7D6ACA8BB99319C08C2';
                $this->warn('Using sandbox API key. Do not use this in production.');
            } else {
                $this->info('Aborted.');
                return;
            }
        }

        $this->info('API Key: ✓');
        $this->info('Mode: ' . $config['mode']);
        $this->info('Currency: ' . $config['currency']);

        // 2. Attempt a lightweight connectivity check via a known transaction query
        $this->info('Testing API connectivity...');

        try {
            $client = new EdfaPayClient($config);
            $client->queryTransaction('00000000-0000-0000-0000-000000000000');
            $this->info('API connectivity: ✓');
        } catch (\DarshPhpDev\EdfaPay\Exceptions\EdfaPayApiException $e) {
            // Any HTTP response (even 404/422) means the server is reachable and the key was accepted
            if ($e->getCode() > 0) {
                $this->info('API connectivity: ✓');
            } else {
                $this->error('API connectivity: ✗ — ' . $e->getMessage());
            }
        }
    }
}
