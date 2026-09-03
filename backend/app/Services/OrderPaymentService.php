<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class OrderPaymentService
{
    /**
     * Credit an order. The callback and the webhook both fire for the same
     * transaction and race routinely, so this has to be idempotent.
     */
    public function markPaid(Order $order): void
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
    }

    public function markFailed(Order $order): void
    {
        if ($order->isPaid()) {
            return;
        }

        $order->update([
            'status' => 'payment_failed',
            'payment_status' => 'failed',
        ]);
    }
}
