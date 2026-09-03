<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Services\CartPricer;
use App\Services\OrderPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function customer(array $items): array
    {
        return [
            'customer_name' => 'Ada Obi',
            'customer_email' => 'ada@example.com',
            'customer_phone' => '08012345678',
            'delivery_address' => '14 Bode Thomas, Surulere, Lagos',
            'items' => $items,
        ];
    }

    public function test_it_totals_a_cart_from_the_price_table(): void
    {
        SiteSetting::current()->update(['delivery_fee_kobo' => 150000]);

        $rice = Product::factory()->create(['price_kobo' => 950000, 'stock' => 10]);
        $garri = Product::factory()->create(['price_kobo' => 420000, 'stock' => 10]);

        $priced = app(CartPricer::class)->price([
            ['product_id' => $rice->id, 'quantity' => 2],
            ['product_id' => $garri->id, 'quantity' => 1],
        ]);

        $this->assertSame(2320000, $priced['subtotal_kobo']);   // 2×9 500 + 4 200
        $this->assertSame(150000, $priced['delivery_fee_kobo']);
        $this->assertSame(2470000, $priced['total_kobo']);
        $this->assertCount(2, $priced['items']);
        $this->assertSame(1900000, $priced['items'][0]['line_total_kobo']);
    }

    public function test_it_merges_a_product_that_appears_twice(): void
    {
        $product = Product::factory()->create(['price_kobo' => 100000, 'stock' => 10]);

        $priced = app(CartPricer::class)->price([
            ['product_id' => $product->id, 'quantity' => 2],
            ['product_id' => $product->id, 'quantity' => 3],
        ]);

        $this->assertCount(1, $priced['items']);
        $this->assertSame(5, $priced['items'][0]['quantity']);
        $this->assertSame(500000, $priced['total_kobo']);
    }

    public function test_it_refuses_more_than_the_stock_on_hand(): void
    {
        $product = Product::factory()->create(['price_kobo' => 100000, 'stock' => 2]);

        $this->expectException(ValidationException::class);

        app(CartPricer::class)->price([
            ['product_id' => $product->id, 'quantity' => 3],
        ]);
    }

    public function test_it_refuses_a_hidden_product(): void
    {
        $product = Product::factory()->hidden()->create(['price_kobo' => 100000, 'stock' => 5]);

        $this->expectException(ValidationException::class);

        app(CartPricer::class)->price([
            ['product_id' => $product->id, 'quantity' => 1],
        ]);
    }

    public function test_it_creates_an_order_with_snapshotted_line_items(): void
    {
        $product = Product::factory()->create([
            'name' => 'Ofada Rice',
            'price_kobo' => 950000,
            'stock' => 5,
        ]);

        $response = $this->postJson('/api/orders', $this->customer([
            ['product_id' => $product->id, 'quantity' => 2],
        ]));

        $response->assertCreated()
            ->assertJsonPath('requires_payment', false)
            ->assertJsonPath('total_kobo', 1900000);

        $order = Order::query()->with('items')->firstOrFail();

        $this->assertSame('Ada Obi', $order->customer_name);
        $this->assertStringStartsWith('SHOP-', $order->reference);
        $this->assertSame(1900000, $order->total_kobo);
        $this->assertCount(1, $order->items);
        $this->assertSame('Ofada Rice', $order->items[0]->product_name);
        $this->assertSame(950000, $order->items[0]->unit_price_kobo);
        $this->assertSame(1900000, $order->items[0]->line_total_kobo);
    }

    public function test_a_client_supplied_price_is_ignored(): void
    {
        $product = Product::factory()->create(['price_kobo' => 950000, 'stock' => 5]);

        $this->postJson('/api/orders', $this->customer([
            ['product_id' => $product->id, 'quantity' => 1, 'unit_price_kobo' => 1, 'line_total_kobo' => 1],
        ]))->assertCreated()->assertJsonPath('total_kobo', 950000);

        $this->assertSame(950000, (int) Order::query()->value('total_kobo'));
    }

    public function test_checkout_validates_the_customer_and_the_cart(): void
    {
        $this->postJson('/api/orders', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['customer_name', 'customer_email', 'delivery_address', 'items']);
    }

    public function test_a_zero_priced_cart_is_rejected_with_422(): void
    {
        $product = Product::factory()->create(['price_kobo' => 0, 'stock' => 5]);

        $this->postJson('/api/orders', $this->customer([
            ['product_id' => $product->id, 'quantity' => 1],
        ]))->assertStatus(422);

        $this->assertSame(0, Order::query()->count());
    }

    public function test_an_order_can_be_read_back_by_reference(): void
    {
        $product = Product::factory()->create(['price_kobo' => 250000, 'stock' => 5]);

        $reference = $this->postJson('/api/orders', $this->customer([
            ['product_id' => $product->id, 'quantity' => 1],
        ]))->json('reference');

        $this->getJson("/api/orders/{$reference}")
            ->assertOk()
            ->assertJsonPath('order.totalKobo', 250000)
            ->assertJsonCount(1, 'order.items');
    }

    public function test_paying_credits_the_order_once_and_draws_down_stock(): void
    {
        $product = Product::factory()->create(['price_kobo' => 250000, 'stock' => 5]);

        $this->postJson('/api/orders', $this->customer([
            ['product_id' => $product->id, 'quantity' => 2],
        ]))->assertCreated();

        $order = Order::query()->firstOrFail();
        $order->update(['status' => 'pending_payment', 'payment_status' => 'pending']);

        $payments = app(OrderPaymentService::class);
        $payments->markPaid($order->refresh());
        $payments->markPaid($order->refresh());   // callback + webhook race

        $order->refresh();

        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('new', $order->status);   // staff queue, not fulfilled
        $this->assertNotNull($order->paid_at);
        $this->assertSame(3, $product->refresh()->stock);   // drawn down exactly once
    }
}
