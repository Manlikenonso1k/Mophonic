<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Services\ReceiptRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptTest extends TestCase
{
    use RefreshDatabase;

    protected function paidOrder(): Order
    {
        $order = Order::factory()->create([
            'reference' => 'SHOP-PAID-1',
            'payment_reference' => 'SHOP-PAID-1',
            'payment_status' => 'paid',
            'paid_at' => now(),
        ]);

        OrderItem::factory()->for($order)->create(['product_name' => 'Suya Spice', 'quantity' => 2]);

        return $order;
    }

    public function test_a_paid_order_yields_a_pdf_over_a_signed_link(): void
    {
        $order = $this->paidOrder();

        $response = $this->get(app(ReceiptRenderer::class)->signedUrl($order));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_an_unsigned_link_is_refused(): void
    {
        $order = $this->paidOrder();

        $this->get("/receipts/{$order->reference}")->assertForbidden();
    }

    public function test_a_tampered_reference_is_refused(): void
    {
        $this->paidOrder();
        $other = Order::factory()->create(['reference' => 'SHOP-PAID-2', 'payment_status' => 'paid']);

        $signed = app(ReceiptRenderer::class)->signedUrl(Order::where('reference', 'SHOP-PAID-1')->first());

        // Swapping the reference inside a valid signature must not work.
        $this->get(str_replace('SHOP-PAID-1', $other->reference, $signed))->assertForbidden();
    }

    public function test_an_unpaid_order_has_no_receipt(): void
    {
        $order = Order::factory()->awaitingPayment()->create(['reference' => 'SHOP-UNPAID-1']);

        $this->get(app(ReceiptRenderer::class)->signedUrl($order))->assertNotFound();
    }

    public function test_the_api_offers_a_receipt_link_only_once_paid(): void
    {
        $paid = $this->paidOrder();
        $unpaid = Order::factory()->awaitingPayment()->create(['reference' => 'SHOP-UNPAID-2']);

        $this->getJson("/api/orders/{$paid->reference}")
            ->assertOk()
            ->assertJsonPath('order.receiptUrl', fn (?string $url) => is_string($url) && str_contains($url, 'signature='));

        $this->getJson("/api/orders/{$unpaid->reference}")
            ->assertOk()
            ->assertJsonPath('order.receiptUrl', null);
    }

    public function test_the_receipt_carries_the_business_and_order_detail(): void
    {
        $order = $this->paidOrder();

        $html = app(ReceiptRenderer::class)->view($order)->render();

        foreach ([
            $order->reference,
            $order->customer_name,
            'Suya Spice',
            'Subtotal',
            'Delivery',
            'Total',
            'paid',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html);
        }

        // Internal fields stay off the customer copy.
        $this->assertStringNotContainsString($order->customer_email, $html);
    }

    public function test_the_staff_copy_adds_the_internal_fields(): void
    {
        $order = $this->paidOrder();

        $html = app(ReceiptRenderer::class)->view($order, staff: true)->render();

        $this->assertStringContainsString($order->customer_email, $html);
        $this->assertStringContainsString('Order status', $html);
        $this->assertStringContainsString('Last updated', $html);
    }
}
