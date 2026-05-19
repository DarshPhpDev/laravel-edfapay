<?php

namespace DarshPhpDev\EdfaPay\Services;

use GuzzleHttp\Client;
use DarshPhpDev\EdfaPay\Builders\EdfaPayPaymentBuilder;
use Exception;

class EdfaPayClient
{
    protected $guzzleClient;
    protected $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        
        // Dynamically match operational environment domains
        $mode = $config['mode'] ?? 'demo';
        $baseUri = $config['urls'][$mode] ?? 'https://demo-api.edfapay.com';

        $this->guzzleClient = new Client([
            'base_uri' => rtrim($baseUri, '/') . '/',
            'timeout'  => 30,
            'headers'  => [
                'X-API-KEY'    => $config['api_key'],
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Entry point method to start a fluent transaction builder chain.
     */
    public function initSale(): EdfaPayPaymentBuilder
    {
        return new EdfaPayPaymentBuilder($this);
    }

    /**
     * Execute the raw POST request to EdfaPay's initiate API endpoint.
     */
    public function sendInitiateRequest(array $payload): array
    {
        try {
            $response = $this->guzzleClient->post('api/v1/payment-gateway/initiate', [
                'json' => $payload,
            ]);

            return json_decode($response->getBody()->getContents(), true) ?? [];
        } catch (Exception $e) {
            // Throw descriptive errors back to the core framework trace stack
            throw new Exception("EdfaPay Request Failed: " . $e->getMessage(), $e->getCode());
        }
    }
}