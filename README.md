<p align="center"><img src="/art/socialcard.png" alt="Laravel EdfaPay"></p>

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

- 🐘 PHP 8.0 | 8.1 | 8.2 | 8.3
- ⚡ Laravel 8.0 | 9.0 | 10.0 | 11.0

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
EDFAPAY_API_KEY=BF15E34275189913593F283D691E39C5849B514E41C8E7D6ACA8BB99319C08C2
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

## 📖 Usage
### 🚀 Initiating a Payment Intent

Leverage the `EdfaPay` Facade and its fluent chain builders to spin up an absolute transaction payload:

```php
use DarshPhpDev\EdfaPay\Facades\EdfaPay;

$response = EdfaPay::initSale()
    ->setOrderId('INV-2026-00941')
    ->setAmount(250.50) // Float or numeric strings are safely converted to precise decimals
    ->setCurrency('SAR')
    ->setUrls('https://yourdomain.com/payment/success', 'https://yourdomain.com/payment/failure')
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