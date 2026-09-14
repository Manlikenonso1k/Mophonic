<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\CartPricer;
use App\Services\OrderNotifier;
use App\Services\PaystackService;
use App\Services\ReceiptRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class OrderController extends Controller
{
    public function __construct(
        private readonly CartPricer $pricer,
        private readonly PaystackService $paystack,
        private readonly OrderNotifier $notifier,
        private readonly ReceiptRenderer $receipts,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_email' => ['required', 'email:rfc', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:40'],
            'delivery_address' => ['required', 'string', 'max:600'],
            'notes' => ['nullable', 'string', 'max:600'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        // Totals are recalculated here; anything the client sent is ignored.
        $priced = $this->pricer->price($data['items']);

        if ($priced['total_kobo'] <= 0) {
            return response()->json(['message' => 'This order has no payable total.'], 422);
        }

        $reference = $this->paystack->generateReference('SHOP');
        $paymentRequired = $this->paystack->isConfigured();

        $order = DB::transaction(function () use ($data, $priced, $reference, $paymentRequired) {
            $order = Order::query()->create([
                'reference' => $reference,
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'],
                'customer_phone' => $data['customer_phone'] ?? null,
                'delivery_address' => $data['delivery_address'],
                'notes' => $data['notes'] ?? null,
                'subtotal_kobo' => $priced['subtotal_kobo'],
                'delivery_fee_kobo' => $priced['delivery_fee_kobo'],
                'total_kobo' => $priced['total_kobo'],
                'status' => $paymentRequired ? 'pending_payment' : 'new',
                'payment_status' => $paymentRequired ? 'pending' : null,
                'payment_reference' => $paymentRequired ? $reference : null,
            ]);

            $order->items()->createMany($priced['items']);

            return $order;
        });

        if (! $paymentRequired) {
            $this->notifier->orderPlaced($order);

            return response()->json([
                'requires_payment' => false,
                'reference' => $order->reference,
                'total_kobo' => $order->total_kobo,
                'message' => 'Order received. We will be in touch to arrange payment.',
            ], 201);
        }

        try {
            $init = $this->paystack->initialize(
                email: $order->customer_email,
                amountKobo: $order->total_kobo,
                reference: $reference,
                callbackUrl: route('shop.payment.callback'),
                metadata: ['order_id' => $order->id],
            );
        } catch (Throwable $e) {
            // No orphan sitting on a dead reference.
            $order->items()->delete();
            $order->delete();

            Log::error('Paystack init failed: '.$e->getMessage());

            return response()->json([
                'message' => 'We could not start the payment. Please try again.',
            ], 422);
        }

        // Announced only once the gateway has accepted it — a failed init
        // deletes the order above, and there is nothing to tell the group.
        $this->notifier->orderPlaced($order);

        return response()->json([
            'requires_payment' => true,
            'reference' => $order->reference,
            'total_kobo' => $order->total_kobo,
            'authorization_url' => $init['authorization_url'] ?? null,
        ], 201);
    }

    /** Lets the confirmation screen show what was ordered. */
    public function show(string $reference): JsonResponse
    {
        $order = Order::query()
            ->with('items')
            ->where('reference', $reference)
            ->firstOrFail();

        return response()->json([
            'order' => [
                'reference' => $order->reference,
                'status' => $order->status,
                'paymentStatus' => $order->payment_status,
                'placedAt' => $order->created_at?->toIso8601String(),
                // Only a paid order has a receipt, and the link is signed.
                'receiptUrl' => $order->isPaid() ? $this->receipts->signedUrl($order) : null,
                'customerName' => $order->customer_name,
                'customerPhone' => $order->customer_phone,
                'deliveryAddress' => $order->delivery_address,
                'subtotalKobo' => $order->subtotal_kobo,
                'deliveryFeeKobo' => $order->delivery_fee_kobo,
                'totalKobo' => $order->total_kobo,
                'items' => $order->items->map(fn ($item) => [
                    'name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'unitPriceKobo' => $item->unit_price_kobo,
                    'lineTotalKobo' => $item->line_total_kobo,
                ])->values(),
            ],
        ]);
    }
}
