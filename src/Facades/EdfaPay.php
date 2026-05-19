<?php

namespace DarshPhpDev\EdfaPay\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \DarshPhpDev\EdfaPay\Builders\EdfaPayPaymentBuilder initSale()
 * @method static array sendInitiateRequest(array $payload)
 * * @see \DarshPhpDev\EdfaPay\Services\EdfaPayClient
 */
class EdfaPay extends Facade
{
    /**
     * Get the registered name of the component inside the container.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'edfapay';
    }
}