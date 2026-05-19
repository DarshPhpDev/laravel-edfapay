# Laravel EdfaPay

[![Latest Version on Packagist](https://img.shields.io/packagist/v/darshphpdev/laravel-edfapay?style=flat-square)](https://packagist.org/packages/darshphpdev/laravel-edfapay)
[![Total Downloads](https://img.shields.io/packagist/dt/darshphpdev/laravel-edfapay?style=flat-square)](https://packagist.org/packages/darshphpdev/laravel-edfapay)
[![License](https://img.shields.io/badge/license-MIT-brightgreen)](LICENSE)

A fluent, modern Laravel wrapper for the EdfaPay v2.0 REST API engine. Effortlessly initiate secure hosted payments and capture transactional callbacks using a clean builder pattern and an decoupled, event-driven webhook architecture.

For full API insights, please review the official [EdfaPay API Documentation Guide](https://docs.edfapay.com/v2.0/docs/welcome-to-edfapay).

## ✨ Features

- 🏎️ **Modern Authentication:** Fully optimized for the modern `X-API-KEY` header system.
- 🌊 **Fluent Payment Builder:** Chainable setters ensure clean, error-free transaction payloads.
- ⚡ **Environment Toggle:** Seamless runtime switching between `demo` and `live` servers.
- 🔔 **Decoupled Webhooks:** Automatic stream-parsing fallback that broadcasts standard Laravel events.
- ⚙️ **Configurable Paths:** Fully customize or completely disable default package webhook routing.
- 🪓 **Cross-Version Stability:** Native Guzzle abstraction ensures stability across various Laravel installations.

## 📋 Requirements

- 🐘 PHP 7.4 | 8.0 | 8.1 | 8.2 | 8.3
- ⚡ Laravel 8.0 | 9.0 | 10.0 | 11.0 | 12.0 | 13.0

## 📥 Installation

You can pull the package into your project via composer:

```bash
composer require darshphpdev/laravel-edfapay
```


## 🔧 Setup
Publish the vendor configuration file to your application's config directory:

```bash
php artisan vendor:publish --provider="DarshPhpDev\EdfaPay\EdfaPayServiceProvider" --tag="edfapay-config"
```

Add your operational keys to your application's environment file (`.env`):

```
EDFAPAY_MODE=demo
EDFAPAY_API_KEY=your-api-key-here
EDFAPAY_CURRENCY=SAR
```

## ⚙️ Configuration

The published configuration maps out as follows under `config/edfapay.php`:

```php
return [
    // Choose operating environment mode: 'demo' or 'live'
    'mode' => env('EDFAPAY_MODE', 'demo'),

    // EdfaPay API Key
    'api_key' => env('EDFAPAY_API_KEY'),

    // API Environment Base URLs
    'urls' => [
        'demo' => '[https://demo-api.edfapay.com](https://demo-api.edfapay.com)',
        'live' => '[https://app-api.edfapay.com](https://app-api.edfapay.com)',
    ],

    // Default Fallback Currency
    'currency' => env('EDFAPAY_CURRENCY', 'SAR'),

    // Webhook Route Settings
    'webhook' => [
        'enable_default_route' => true,
        'path' => 'api/v1/payments/edfapay/webhook',
        'middleware' => ['api'],
    ],
];
```

## 🩺 Verifying Your Setup

After configuration, run the following command to validate your credentials and confirm API connectivity:

```bash
php artisan edfapay:check
```

A successful output looks like:

```
API Key: ✓
Mode: demo
Currency: SAR
Testing API connectivity...
API connectivity: ✓
```

If `EDFAPAY_API_KEY` is missing, the package will throw a `RuntimeException` on first use to prevent silent failures in production.

## 📖 Usage
### 🚀 Initiating a Payment Intent

Leverage the EdfaPay Facade and its fluent chain builders to spin up an absolute transaction payload. 
Per the EdfaPay Webhook Docs, you can set your notification capture endpoint dynamically per-transaction using setNotificationUrl() or rely on your global merchant dashboard webhook configurations.

```php
use DarshPhpDev\EdfaPay\Facades\EdfaPay;

$response = EdfaPay::initSale()
    ->setOrderId('INV-2026-00941')
    ->setAmount(250.50) // Float or numeric strings are safely converted to precise decimals
    ->setCurrency('SAR')
    ->setUrls('https://yourdomain.com/payment/success', 'https://yourdomain.com/payment/failure')
    ->setNotificationUrl('https://yourdomain.com/api/v1/payments/edfapay/webhook') // Dynamic dynamic webhook link option
    ->setCustomerDetails([
        'name'  => 'Mustafa Ahmed',
        'email' => 'customer@domain.com',
        'phone' => '+966500000001',
    ])
    ->setAddress([
        'country' => 'SA',
        'city'    => 'Riyadh',
        'address' => 'Olaya District',
    ])
    ->initiate();

// Extract the gateway checkout URL from the response array
if (isset($response['redirectUrl'])) {
    return redirect()->away($response['redirectUrl']);
}
```

### ⚠️ Validation & Error Handling

Calling `initiate()` automatically validates the payload before dispatching the request. The following fields are required: `orderId`, `currency`, `amount`, `customerDetails.name`, `customerDetails.email`, `customerDetails.phone`, `successUrl`, and `failureUrl`.

Use the package's typed exceptions to handle failures cleanly:

```php
use DarshPhpDev\EdfaPay\Facades\EdfaPay;
use DarshPhpDev\EdfaPay\Exceptions\EdfaPayValidationException;
use DarshPhpDev\EdfaPay\Exceptions\EdfaPayApiException;

try {
    $response = EdfaPay::initSale()
        ->setOrderId('INV-2026-00941')
        ->setAmount(250.50)
        ->setUrls('https://yourdomain.com/payment/success', 'https://yourdomain.com/payment/failure')
        ->setCustomerDetails(['name' => 'Mustafa Ahmed', 'email' => 'customer@domain.com', 'phone' => '+966500000001'])
        ->initiate();
} catch (EdfaPayValidationException $e) {
    // Missing or invalid required fields — no HTTP call was made
    dd($e->getErrors()); // ['customerDetails.phone is required', ...]
} catch (EdfaPayApiException $e) {
    // HTTP request was made but EdfaPay returned an error
    dd($e->getCode(), $e->getResponseBody());
}
```

Sample edfaapay successful response:
```json
{
  "code": 200,
  "message": "Success",
  "errorCode": null,
  "data": {
    "redirectUrl": "https://edfa-demo.edfapay.com/pay/checkout?sessionId=fa522cc3-7b92-467b-b132-40794bf4734f",
    "id": "fa522cc3-7b92-467b-b132-40794bf4734f"
  }
}
```

## 🔍 Querying a Transaction Status

Use `queryTransaction()` to fetch the current status of any transaction by its ID. Useful for reconciliation jobs or when a webhook is missed.

```php
use DarshPhpDev\EdfaPay\Facades\EdfaPay;
use DarshPhpDev\EdfaPay\Exceptions\EdfaPayApiException;

try {
    $response = EdfaPay::queryTransaction('fa522cc3-7b92-467b-b132-40794bf4734f');
} catch (EdfaPayApiException $e) {
    dd($e->getCode(), $e->getResponseBody());
}
```

## 💸 Initiating a Refund

Use the `initRefund()` fluent builder to refund a previously approved transaction. `transactionId` and `amount` are required. `reason` is optional.

```php
use DarshPhpDev\EdfaPay\Facades\EdfaPay;
use DarshPhpDev\EdfaPay\Exceptions\EdfaPayValidationException;
use DarshPhpDev\EdfaPay\Exceptions\EdfaPayApiException;

try {
    $response = EdfaPay::initRefund()
        ->setTransactionId('fa522cc3-7b92-467b-b132-40794bf4734f')
        ->setAmount(250.50)
        ->setReason('Customer requested cancellation') // optional
        ->refund();
} catch (EdfaPayValidationException $e) {
    dd($e->getErrors());
} catch (EdfaPayApiException $e) {
    dd($e->getCode(), $e->getResponseBody());
}
```

## 🔔 Handling Webhook Notifications (IPN)

This package automatically captures postback webhook stream payloads under the route defined in your configuration file, writes production metrics, and converts them into a standard decoupled event payload.

### 1. Register a Listener
Map your custom listener to the package event inside your application's `App\Providers\EventServiceProvider` array matrix:

```php
protected $listen = [
    \DarshPhpDev\EdfaPay\Events\EdfaPayWebhookReceived::class => [
        \App\Listeners\ProcessEdfaPayCallback::class,
    ],
];
```

### 2. Write the Database Sync Logic
Inside your listener, fetch the verified data payload seamlessly to run your model syncs:

```php
namespace App\Listeners;

use DarshPhpDev\EdfaPay\Events\EdfaPayWebhookReceived;
use App\Models\Order;

class ProcessEdfaPayCallback
{
    public function handle(EdfaPayWebhookReceived $event)
    {
        $payload = $event->payload;

        $orderId       = $payload['orderId'] ?? null;
        $status        = $payload['status'] ?? null; // e.g. 'Approved' or 'Declined'
        $transactionId = $payload['transactionId'] ?? null;

        $order = Order::where('id', $orderId)->firstOrFail();

        $order->update([
            'status'         => strtolower($status) === 'approved' ? 'paid' : 'failed',
            'transaction_id' => $transactionId,
        ]);
    }
}
```

## 🛡️ Security

If you discover any security-related issues, please email mustafa.softcode@gmail.com instead of using the issue tracker.

## 👨‍💻 Credits
- [Mustafa Ahmed](https://github.com/darshphpdev)

## 📄 License

This package is open-source software licensed under the [MIT License](https://opensource.org/licenses/MIT).
