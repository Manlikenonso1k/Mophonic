<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\ReceiptRenderer;
use Symfony\Component\HttpFoundation\Response;

class ReceiptController extends Controller
{
    public function __construct(private readonly ReceiptRenderer $receipts) {}

    /**
     * Reached only through a signed link, and only once the money is in — an
     * unpaid order has no receipt to give.
     */
    public function __invoke(string $reference): Response
    {
        $order = Order::query()
            ->with('items')
            ->where('reference', $reference)
            ->firstOrFail();

        abort_unless($order->isPaid(), 404);

        return $this->receipts->download($order);
    }
}
