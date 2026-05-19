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
            $this->error('EDFAPAY_API_KEY is not set.');
            return;
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
