<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class OrderPaymentService
{
    public function __construct(private readonly OrderNotifier $notifier) {}

    /**
     * Credit an order. The callback and the webhook both fire for the same
     * transaction and race routinely, so this has to be idempotent — which is
     * also what keeps the Telegram group to exactly one message per payment.
     *
     * @param  array<string, mixed>  $gateway  The verified Paystack payload.
     */
    public function markPaid(Order $order, array $gateway = []): void
    {
        if ($order->isPaid()) {
            return;
        }

        DB::transaction(function () use ($order): void {
            $order->update([
                // Paid moves it into the staff queue, not past it.
                'status' => 'new',
                'payment_status' => 'paid',
                'paid_at' => now(),
            ]);

            foreach ($order->items as $item) {
                if ($item->product_id === null) {
                    continue;
                }

                Product::query()
                    ->whereKey($item->product_id)
                    ->where('stock', '>=', $item->quantity)
                    ->decrement('stock', $item->quantity);
            }
        });

        $this->notifier->paymentSucceeded($order, $gateway);
    }

    public function markFailed(Order $order, ?string $reason = null): void
    {
        if ($order->isPaid() || $order->payment_status === 'failed') {
            return;
        }

        $order->update([
            'status' => 'payment_failed',
            'payment_status' => 'failed',
        ]);

        $this->notifier->paymentFailed($order, $reason);
    }
}
