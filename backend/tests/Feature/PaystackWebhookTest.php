<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PaystackWebhookTest extends TestCase
{
    use RefreshDatabase;

    private string $secret = 'sk_test_webhooktestkey';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.paystack.secret_key', $this->secret);
    }

    /** @param array<string, mixed> $payload */
    private function sendWebhook(array $payload, ?string $signature = null): TestResponse
    {
        $body = json_encode($payload, JSON_THROW_ON_ERROR);

        return $this->call(
            'POST',
            '/webhooks/paystack',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_PAYSTACK_SIGNATURE' => $signature ?? hash_hmac('sha512', $body, $this->secret),
            ],
            content: $body,
        );
    }

    public function test_a_bad_signature_is_rejected(): void
    {
        $this->sendWebhook(['event' => 'charge.success', 'data' => ['reference' => 'SHOP-X-1']], 'nonsense')
            ->assertStatus(401);
    }

    public function test_a_valid_signature_with_an_unknown_reference_is_acknowledged(): void
    {
        $this->sendWebhook(['event' => 'charge.success', 'data' => ['reference' => 'SHOP-NOPE-1']])
            ->assertOk();
    }

    public function test_an_unrelated_event_is_acknowledged(): void
    {
        $this->sendWebhook(['event' => 'transfer.failed', 'data' => ['reference' => 'SHOP-X-1']])
            ->assertOk();
    }

    public function test_it_credits_a_pending_order(): void
    {
        $product = Product::factory()->create(['price_kobo' => 250000, 'stock' => 4]);

        $order = Order::factory()->awaitingPayment()->create([
            'reference' => 'SHOP-CREDIT-1',
            'payment_reference' => 'SHOP-CREDIT-1',
            'subtotal_kobo' => 250000,
            'total_kobo' => 250000,
        ]);

        OrderItem::factory()->for($order)->for($product)->create([
            'product_name' => $product->name,
            'unit_price_kobo' => 250000,
            'quantity' => 1,
            'line_total_kobo' => 250000,
        ]);

        $this->sendWebhook(['event' => 'charge.success', 'data' => ['reference' => 'SHOP-CREDIT-1']])
            ->assertOk();

        $order->refresh();

        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('new', $order->status);
        $this->assertSame(3, $product->refresh()->stock);
    }
}
