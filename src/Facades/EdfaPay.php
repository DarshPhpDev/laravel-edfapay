<?php

namespace DarshPhpDev\EdfaPay\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \DarshPhpDev\EdfaPay\Builders\EdfaPayPaymentBuilder initSale()
 * @method static \DarshPhpDev\EdfaPay\Builders\EdfaPayRefundBuilder initRefund()
 * @method static array queryTransaction(string $transactionId)
 * @see \DarshPhpDev\EdfaPay\Services\EdfaPayClient
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