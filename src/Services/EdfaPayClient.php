<?php

namespace DarshPhpDev\EdfaPay\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use DarshPhpDev\EdfaPay\Builders\EdfaPayPaymentBuilder;
use DarshPhpDev\EdfaPay\Builders\EdfaPayRefundBuilder;
use DarshPhpDev\EdfaPay\Exceptions\EdfaPayApiException;

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
     * Entry point method to start a fluent refund builder chain.
     */
    public function initRefund(): EdfaPayRefundBuilder
    {
        return new EdfaPayRefundBuilder($this);
    }

    /**
     * Execute the raw POST request to EdfaPay's initiate API endpoint.
     */
    public function sendInitiateRequest(array $payload): array
    {
        Log::info('EDFAPAY: Initiating payment.', ['order_id' => $payload['orderId'] ?? null, 'amount' => $payload['amount'] ?? null]);
        return $this->post('api/v1/payment-gateway/initiate', $payload);
    }

    /**
     * Execute the raw POST request to EdfaPay's refund API endpoint.
     */
    public function sendRefundRequest(array $payload): array
    {
        Log::info('EDFAPAY: Initiating refund.', ['transaction_id' => $payload['transactionId'] ?? null, 'amount' => $payload['amount'] ?? null]);
        return $this->post('api/v1/payment-gateway/s2s/refund', $payload);
    }

    /**
     * Query a transaction's current status by its ID.
     */
    public function queryTransaction(string $transactionId): array
    {
        try {
            $response = $this->guzzleClient->get('api/v1/transactions/filterTransaction', [
                'query' => ['id' => $transactionId],
            ]);
            return json_decode($response->getBody()->getContents(), true) ?? [];
        } catch (RequestException $e) {
            $statusCode   = $e->getResponse() ? $e->getResponse()->getStatusCode() : 0;
            $responseBody = $e->getResponse() ? json_decode($e->getResponse()->getBody()->getContents(), true) ?? [] : [];
            Log::error('EDFAPAY: Transaction query failed.', ['transaction_id' => $transactionId, 'status_code' => $statusCode]);
            throw new EdfaPayApiException('EdfaPay API request failed: ' . $e->getMessage(), $statusCode, $responseBody);
        } catch (GuzzleException $e) {
            Log::error('EDFAPAY: Transaction query connection error.', ['transaction_id' => $transactionId, 'error' => $e->getMessage()]);
            throw new EdfaPayApiException('EdfaPay connection error: ' . $e->getMessage(), 0, []);
        }
    }

    /**
     * Shared HTTP POST handler with typed exception wrapping.
     */
    protected function post(string $endpoint, array $payload): array
    {
        try {
            $response = $this->guzzleClient->post($endpoint, ['json' => $payload]);
            $decoded  = json_decode($response->getBody()->getContents(), true) ?? [];
            Log::info('EDFAPAY: API call successful.', ['endpoint' => $endpoint]);
            return $decoded;
        } catch (RequestException $e) {
            $statusCode   = $e->getResponse() ? $e->getResponse()->getStatusCode() : 0;
            $responseBody = $e->getResponse() ? json_decode($e->getResponse()->getBody()->getContents(), true) ?? [] : [];
            Log::error('EDFAPAY: API call failed.', ['endpoint' => $endpoint, 'status_code' => $statusCode, 'response' => $responseBody]);
            throw new EdfaPayApiException('EdfaPay API request failed: ' . $e->getMessage(), $statusCode, $responseBody);
        } catch (GuzzleException $e) {
            Log::error('EDFAPAY: API connection error.', ['endpoint' => $endpoint, 'error' => $e->getMessage()]);
            throw new EdfaPayApiException('EdfaPay connection error: ' . $e->getMessage(), 0, []);
        }
    }
}