<?php

namespace Tests\Feature;

use App\Jobs\SendTelegramMessage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\OrderPaymentService;
use App\Services\TelegramNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TelegramNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'telegram.enabled' => true,
            'telegram.bot_token' => 'test-token',
            'telegram.chat_id' => '-1001234567890',
        ]);
    }

    public function test_placing_an_order_queues_a_notification(): void
    {
        Queue::fake();

        $product = Product::factory()->create(['price_kobo' => 250000, 'stock' => 10]);

        $this->postJson('/api/orders', [
            'customer_name' => 'Ada Okoro',
            'customer_email' => 'ada@example.com',
            'customer_phone' => '08012345678',
            'delivery_address' => '14 Marina Road, Lagos',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ])->assertCreated();

        Queue::assertPushed(SendTelegramMessage::class, function (SendTelegramMessage $job) use ($product) {
            return str_contains($job->message, '🧾 <b>New order</b>')
                && str_contains($job->message, 'Ada Okoro')
                && str_contains($job->message, '08012345678')
                && str_contains($job->message, '14 Marina Road, Lagos')
                && str_contains($job->message, $product->name.' × 2');
        });
    }

    public function test_a_paid_order_notifies_once_even_when_callback_and_webhook_race(): void
    {
        Queue::fake();

        $order = Order::factory()->awaitingPayment()->create(['total_kobo' => 500000]);
        OrderItem::factory()->for($order)->create();

        $payments = app(OrderPaymentService::class);
        $gateway = ['reference' => 'PSK-REF-9', 'channel' => 'card'];

        // Both handlers credit the same transaction.
        $payments->markPaid($order->fresh(), $gateway);
        $payments->markPaid($order->fresh(), $gateway);

        Queue::assertPushed(SendTelegramMessage::class, 1);
        Queue::assertPushed(SendTelegramMessage::class, function (SendTelegramMessage $job) {
            return str_contains($job->message, '✅ <b>Payment received</b>')
                && str_contains($job->message, '<code>PSK-REF-9</code>')
                && str_contains($job->message, 'Channel: card');
        });
    }

    public function test_a_failed_payment_reports_the_gateway_reason(): void
    {
        Queue::fake();

        $order = Order::factory()->awaitingPayment()->create();

        app(OrderPaymentService::class)->markFailed($order, 'Insufficient funds');

        Queue::assertPushed(SendTelegramMessage::class, function (SendTelegramMessage $job) {
            return str_contains($job->message, '⚠️ <b>Payment failed</b>')
                && str_contains($job->message, 'Reason: Insufficient funds');
        });
    }

    public function test_customer_names_are_escaped_for_html_parse_mode(): void
    {
        Queue::fake();

        $order = Order::factory()->awaitingPayment()->create([
            'customer_name' => 'Ada & <b>Sons</b>',
        ]);

        app(OrderPaymentService::class)->markFailed($order, null);

        Queue::assertPushed(SendTelegramMessage::class, function (SendTelegramMessage $job) {
            return str_contains($job->message, 'Ada &amp; &lt;b&gt;Sons&lt;/b&gt;')
                && ! str_contains($job->message, '<b>Sons</b>');
        });
    }

    public function test_it_posts_html_to_the_telegram_api(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);

        $this->assertTrue(app(TelegramNotifier::class)->send('hello'));

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.telegram.org/bottest-token/sendMessage'
                && $request['chat_id'] === '-1001234567890'
                && $request['parse_mode'] === 'HTML'
                && $request['disable_web_page_preview'] === true
                && $request['text'] === 'hello';
        });
    }

    public function test_a_telegram_outage_is_logged_and_swallowed(): void
    {
        Log::spy();
        Http::fake(['api.telegram.org/*' => Http::response('nope', 500)]);

        $this->assertFalse(app(TelegramNotifier::class)->send('hello'));

        Log::shouldHaveReceived('error')->once();
    }

    public function test_checkout_still_succeeds_when_telegram_is_unreachable(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response('nope', 500)]);

        $product = Product::factory()->create(['price_kobo' => 250000, 'stock' => 5]);

        $this->postJson('/api/orders', [
            'customer_name' => 'Ada Okoro',
            'customer_email' => 'ada@example.com',
            'delivery_address' => '14 Marina Road, Lagos',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertCreated();

        $this->assertDatabaseHas('orders', ['customer_email' => 'ada@example.com']);
    }

    public function test_nothing_is_sent_when_telegram_is_disabled(): void
    {
        config(['telegram.enabled' => false]);

        Http::fake();

        $this->assertFalse(app(TelegramNotifier::class)->send('hello'));

        Http::assertNothingSent();
    }
}
