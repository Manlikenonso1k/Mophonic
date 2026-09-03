<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderPaymentService;
use App\Services\PaystackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class ShopPaymentController extends Controller
{
    public function __construct(
        private readonly PaystackService $paystack,
        private readonly OrderPaymentService $payments,
    ) {}

    /**
     * Paystack's dashboard callback is one global URL with nowhere to put an
     * id, so the order is resolved from the reference it appends.
     */
    public function callback(Request $request): RedirectResponse
    {
        $reference = (string) $request->query('reference', $request->query('trxref', ''));
        $order = Order::query()->where('payment_reference', $reference)->first();

        $shopUrl = rtrim((string) config('app.frontend_url'), '/');

        if (! $order) {
            return redirect()->away($shopUrl.'/shop?payment=unknown');
        }

        try {
            $data = $this->paystack->verify($reference);
        } catch (Throwable $e) {
            Log::error('Paystack verify failed: '.$e->getMessage());

            return redirect()->away($shopUrl.'/shop?payment=unverified&reference='.$order->reference);
        }

        if (($data['status'] ?? '') === 'success') {
            $this->payments->markPaid($order);

            return redirect()->away($shopUrl.'/shop/thank-you?reference='.$order->reference);
        }

        $this->payments->markFailed($order);

        return redirect()->away($shopUrl.'/shop?payment=failed&reference='.$order->reference);
    }
}
