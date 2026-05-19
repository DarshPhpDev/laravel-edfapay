<?php

namespace DarshPhpDev\EdfaPay\Builders;

use DarshPhpDev\EdfaPay\Services\EdfaPayClient;
use DarshPhpDev\EdfaPay\Exceptions\EdfaPayValidationException;

class EdfaPayPaymentBuilder
{
    protected $client;
    protected $data = [];

    public function __construct(EdfaPayClient $client)
    {
        $this->client = $client;
        
        // Populate smart defaults from configuration
        $this->data['currency'] = config('edfapay.currency', 'SAR');
        $this->data['recurringInit'] = 'N';
        $this->data['paymentMethod'] = 'card';
    }

    public function setOrderId(string $orderId): self
    {
        $this->data['orderId'] = $orderId;
        return $this;
    }

    public function setCurrency(string $currency): self
    {
        $this->data['currency'] = $currency;
        return $this;
    }

    public function setAmount($amount): self
    {
        $this->data['amount'] = number_format((float)$amount, 2, '.', '');
        return $this;
    }

    public function setCustomerDetails(array $details): self
    {
        $this->data['customerDetails'] = [
            'name'      => $details['name'] ?? null,
            'email'     => $details['email'] ?? null,
            'phone'     => $details['phone'] ?? null,
            'idNumber'  => $details['idNumber'] ?? null,
            'idType'    => $details['idType'] ?? 'National Id',
            'taxNumber' => $details['taxNumber'] ?? null,
        ];
        return $this;
    }

    public function setAddress(array $address): self
    {
        $this->data['address'] = [
            'country' => $address['country'] ?? 'SA',
            'state'   => $address['state'] ?? null,
            'city'    => $address['city'] ?? null,
            'address' => $address['address'] ?? null,
            'zip'     => $address['zip'] ?? null,
        ];
        return $this;
    }

    public function setRecurringInit(string $flag = 'N'): self
    {
        $this->data['recurringInit'] = $flag;
        return $this;
    }

    public function setUrls(string $successUrl, string $failureUrl): self
    {
        $this->data['successUrl'] = $successUrl;
        $this->data['failureUrl'] = $failureUrl;
        return $this;
    }

    public function setPaymentMethod(string $method): self
    {
        $this->data['paymentMethod'] = $method;
        return $this;
    }

    public function setActivityId($activityId): self
    {
        $this->data['activityId'] = $activityId;
        return $this;
    }

    /**
     * Set a dynamic transaction webhook notification URL.
     * Or set via merchant dashboard webhook configuration.
     *
     * @param string $url
     * @return $this
     */
    public function setNotificationUrl(string $url): self
    {
        $this->data['notification_url'] = $url;
        return $this;
    }

    /**
     * Build the payload and dispatch it through the client engine.
     */
    public function initiate(): array
    {
        $this->validate();
        return $this->client->sendInitiateRequest($this->data);
    }

    /**
     * Validate required fields before dispatching the request.
     *
     * @throws EdfaPayValidationException
     */
    protected function validate(): void
    {
        $errors = [];

        if (empty($this->data['orderId']))                          $errors[] = 'orderId is required';
        if (empty($this->data['currency']))                         $errors[] = 'currency is required';
        if (empty($this->data['amount']))                           $errors[] = 'amount is required';
        if (empty($this->data['customerDetails']['name']))          $errors[] = 'customerDetails.name is required';
        if (empty($this->data['customerDetails']['email']))         $errors[] = 'customerDetails.email is required';
        if (empty($this->data['customerDetails']['phone']))         $errors[] = 'customerDetails.phone is required';
        if (empty($this->data['successUrl']))                       $errors[] = 'successUrl is required';
        if (empty($this->data['failureUrl']))                       $errors[] = 'failureUrl is required';

        if (!empty($errors)) {
            throw new EdfaPayValidationException($errors);
        }
    }
}