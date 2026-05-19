<?php

namespace DarshPhpDev\EdfaPay\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;

class EdfaPayWebhookReceived
{
    use Dispatchable, SerializesModels;

    /**
     * The raw, parsed JSON data payload array from EdfaPay.
     *
     * @var array
     */
    public $payload;

    /**
     * Create a new event instance.
     *
     * @param array $payload
     */
    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }
}