<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderPaymentService;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

class PaystackWebhookController extends Controller
{
    public function __construct(
        private readonly PaystackService $paystack,
        private readonly OrderPaymentService $payments,
    ) {}

    /**
     * One endpoint for every Paystack event — the dashboard only allows one
     * URL per mode — dispatching on the reference prefix.
     */
    public function handle(Request $request): Response
    {
        $payload = $request->getContent();
        $signature = (string) $request->header('x-paystack-signature', '');

        if (! $this->paystack->verifyWebhookSignature($payload, $signature)) {
            return response('Unauthorized', 401);
        }

        $event = json_decode($payload, true) ?: [];

        if (($event['event'] ?? '') !== 'charge.success') {
            return response('OK', 200);
        }

        $reference = $event['data']['reference'] ?? null;

        try {
            if (is_string($reference) && str_starts_with($reference, 'SHOP-')) {
                $order = Order::query()->where('payment_reference', $reference)->first();

                if ($order) {
                    $this->payments->markPaid($order);
                }
            }
        } catch (Throwable $e) {
            // Never 500 back: Paystack would retry indefinitely.
            Log::error('Webhook processing failed: '.$e->getMessage());
        }

        return response('OK', 200);
    }
}
