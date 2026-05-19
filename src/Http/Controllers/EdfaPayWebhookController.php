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
        $payload = $request->all();

        if (empty($payload)) {
            $payload = json_decode($request->getContent(), true) ?? [];
        }

        if (empty($payload) || !isset($payload['orderId'])) {
            Log::warning('EDFAPAY-WEBHOOK: Malformed or empty payload received.', [
                'raw_content' => $request->getContent(),
            ]);
            return response()->json(['status' => 'error', 'message' => 'Malformed data structure.'], 200);
        }

        $orderId       = $payload['orderId'];
        $status        = $payload['status'] ?? 'UNKNOWN';
        $transactionId = $payload['transactionId'] ?? 'UNKNOWN';

        Log::info('EDFAPAY-WEBHOOK: Payload received.', [
            'order_id'       => $orderId,
            'status'         => $status,
            'transaction_id' => $transactionId,
        ]);

        try {
            event(new EdfaPayWebhookReceived($payload));
        } catch (\Throwable $e) {
            Log::error('EDFAPAY-WEBHOOK: Event listener threw an exception.', [
                'order_id' => $orderId,
                'error'    => $e->getMessage(),
            ]);
            return response()->json(['status' => 'error', 'message' => 'Webhook processing failed.'], 200);
        }

        return response()->json(['status' => 'success', 'message' => 'Webhook received and processed successfully.'], 200);
    }
}