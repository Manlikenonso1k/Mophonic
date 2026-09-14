<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Services\PaystackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PaymentCallbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.frontend_url' => 'https://shop.example.com']);
    }

    protected function fakePaystack(array $verifyResult): void
    {
        $paystack = Mockery::mock(PaystackService::class);
        $paystack->shouldReceive('verify')->andReturn($verifyResult);
        $paystack->shouldReceive('isConfigured')->andReturnTrue();

        $this->app->instance(PaystackService::class, $paystack);
    }

    public function test_a_successful_payment_lands_on_the_order_confirmation(): void
    {
        $this->fakePaystack(['status' => 'success', 'reference' => 'PSK-1', 'channel' => 'card']);

        $order = Order::factory()->awaitingPayment()->create(['reference' => 'SHOP-ABC-1', 'payment_reference' => 'SHOP-ABC-1']);
        OrderItem::factory()->for($order)->create();

        $this->get('/shop/payment/callback?reference=SHOP-ABC-1')
            ->assertRedirect('https://shop.example.com/order/SHOP-ABC-1');

        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_a_failed_payment_still_lands_on_the_order_not_the_homepage(): void
    {
        $this->fakePaystack(['status' => 'failed', 'gateway_response' => 'Insufficient funds']);

        $order = Order::factory()->awaitingPayment()->create(['reference' => 'SHOP-ABC-2', 'payment_reference' => 'SHOP-ABC-2']);

        $this->get('/shop/payment/callback?reference=SHOP-ABC-2')
            ->assertRedirect('https://shop.example.com/order/SHOP-ABC-2?payment=failed');
    }

    public function test_it_reads_the_reference_from_trxref_too(): void
    {
        $this->fakePaystack(['status' => 'success', 'reference' => 'PSK-3']);

        $order = Order::factory()->awaitingPayment()->create(['reference' => 'SHOP-ABC-3', 'payment_reference' => 'SHOP-ABC-3']);
        OrderItem::factory()->for($order)->create();

        $this->get('/shop/payment/callback?trxref=SHOP-ABC-3')
            ->assertRedirect('https://shop.example.com/order/SHOP-ABC-3');
    }

    public function test_the_confirmation_resolves_from_the_reference_long_after_the_cart_is_gone(): void
    {
        $order = Order::factory()->create(['reference' => 'SHOP-ABC-4', 'payment_status' => 'paid']);
        OrderItem::factory()->for($order)->create(['product_name' => 'Suya Spice', 'quantity' => 2]);

        $this->getJson('/api/orders/SHOP-ABC-4')
            ->assertOk()
            ->assertJsonPath('order.reference', 'SHOP-ABC-4')
            ->assertJsonPath('order.paymentStatus', 'paid')
            ->assertJsonPath('order.items.0.name', 'Suya Spice')
            ->assertJsonPath('order.customerName', $order->customer_name)
            ->assertJsonPath('order.deliveryAddress', $order->delivery_address);
    }

    public function test_it_falls_back_to_the_app_url_when_no_frontend_url_is_configured(): void
    {
        config(['app.frontend_url' => '', 'app.url' => 'https://mophonik.example.com']);
        $this->fakePaystack(['status' => 'success', 'reference' => 'PSK-5']);

        $order = Order::factory()->awaitingPayment()->create([
            'reference' => 'SHOP-ABC-5',
            'payment_reference' => 'SHOP-ABC-5',
        ]);
        OrderItem::factory()->for($order)->create();

        $this->get('/shop/payment/callback?reference=SHOP-ABC-5')
            ->assertRedirect('https://mophonik.example.com/order/SHOP-ABC-5');
    }
}
