<?php

namespace DarshPhpDev\EdfaPay\Builders;

use DarshPhpDev\EdfaPay\Services\EdfaPayClient;
use DarshPhpDev\EdfaPay\Exceptions\EdfaPayValidationException;

class EdfaPayRefundBuilder
{
    protected $client;
    protected $data = [];

    public function __construct(EdfaPayClient $client)
    {
        $this->client = $client;
    }

    public function setTransactionId(string $transactionId): self
    {
        $this->data['transactionId'] = $transactionId;
        return $this;
    }

    public function setAmount($amount): self
    {
        $this->data['amount'] = number_format((float)$amount, 2, '.', '');
        return $this;
    }

    public function setReason(string $reason): self
    {
        $this->data['reason'] = $reason;
        return $this;
    }

    /**
     * Validate and dispatch the refund request.
     */
    public function refund(): array
    {
        $this->validate();
        return $this->client->sendRefundRequest($this->data);
    }

    /**
     * @throws EdfaPayValidationException
     */
    protected function validate(): void
    {
        $errors = [];

        if (empty($this->data['transactionId'])) $errors[] = 'transactionId is required';
        if (empty($this->data['amount']))         $errors[] = 'amount is required';

        if (!empty($errors)) {
            throw new EdfaPayValidationException($errors);
        }
    }
}
