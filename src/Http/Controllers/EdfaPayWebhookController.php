<?php

namespace DarshPhpDev\EdfaPay\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use DarshPhpDev\EdfaPay\Events\EdfaPayWebhookReceived;

class EdfaPayWebhookController extends Controller
{
    /**
     * Handle incoming EdfaPay IPN Webhook callback notifications.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request)
    {
        // 1. Extract payload safely using standard format or raw stream direct binary fallback
        $payload = $request->all();

        if (empty($payload)) {
            $payload = json_decode($request->getContent(), true) ?? [];
        }

        $orderId = $payload['orderId'] ?? 'UNKNOWN';
        $status = $payload['status'] ?? 'UNKNOWN';
        $transactionId = $payload['transactionId'] ?? 'UNKNOWN';

        // 2. Production Debug Tracking Logs
        Log::info('EDFAPAY-PACKAGE-WEBHOOK: Received callback request notification.', [
            'order_id'       => $orderId,
            'status'         => $status,
            'transaction_id' => $transactionId,
            'payload_size'   => count($payload),
        ]);

        if (empty($payload) || !isset($payload['orderId'])) {
            Log::warning('EDFAPAY-PACKAGE-WEBHOOK: Aborting processing. Malformed or empty payload context received.', [
                'raw_content' => $request->getContent()
            ]);
            
            // Still respond with 200 or 400 depending on gateway requirement. 
            // Returning 200 ensures the gateway stops retrying broken dead streams.
            return response()->json(['status' => 'error', 'message' => 'Malformed data structure.'], 200);
        }

        // 3. Dispatch the event asynchronously across the Laravel core system kernel
        event(new EdfaPayWebhookReceived($payload));

        // 4. Return clean, definitive 200 response back to EdfaPay servers
        return response()->json([
            'status'  => 'success',
            'message' => 'Webhook received and processed successfully.'
        ], 200);
    }
}