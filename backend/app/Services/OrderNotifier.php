<?php

namespace App\Services;

use App\Jobs\SendTelegramMessage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Support\Money;

/**
 * Composes the Telegram messages for shop events and queues them.
 *
 * Everything customer-supplied is escaped before it reaches the HTML parse
 * mode — a name containing `&` or `<` would otherwise have Telegram reject
 * the whole message.
 */
class OrderNotifier
{
    public function orderPlaced(Order $order): void
    {
        $lines = [
            '🧾 <b>New order</b>',
            '',
            'Ref: <code>'.$this->escape($order->reference).'</code>',
            'Customer: <b>'.$this->escape($order->customer_name).'</b>',
        ];

        if (filled($order->customer_phone)) {
            $lines[] = 'Phone: <code>'.$this->escape($order->customer_phone).'</code>';
        }

        if (filled($order->delivery_address)) {
            $lines[] = 'Deliver to: '.$this->escape($order->delivery_address);
        }

        $lines[] = '';
        $lines[] = '<b>Items</b>';

        foreach ($order->items as $item) {
            $lines[] = $this->itemLine($item);
        }

        $lines[] = '';
        $lines[] = 'Subtotal: '.$this->escape(Money::format($order->subtotal_kobo));
        $lines[] = 'Delivery: '.$this->escape(Money::format($order->delivery_fee_kobo));
        $lines[] = 'Total: <b>'.$this->escape(Money::format($order->total_kobo)).'</b>';

        $this->queue($lines);
    }

    /** @param array<string, mixed> $gateway The verified Paystack payload. */
    public function paymentSucceeded(Order $order, array $gateway = []): void
    {
        $lines = [
            '✅ <b>Payment received</b>',
            '',
            'Ref: <code>'.$this->escape($order->reference).'</code>',
            'Amount: <b>'.$this->escape(Money::format($order->total_kobo)).'</b>',
        ];

        if (filled($gateway['reference'] ?? null)) {
            $lines[] = 'Gateway ref: <code>'.$this->escape((string) $gateway['reference']).'</code>';
        }

        if (filled($gateway['channel'] ?? null)) {
            $lines[] = 'Channel: '.$this->escape((string) $gateway['channel']);
        }

        $lines[] = 'Customer: '.$this->escape($order->customer_name);

        $this->queue($lines);
    }

    public function paymentFailed(Order $order, ?string $reason = null): void
    {
        $lines = [
            '⚠️ <b>Payment failed</b>',
            '',
            'Ref: <code>'.$this->escape($order->reference).'</code>',
            'Amount: <b>'.$this->escape(Money::format($order->total_kobo)).'</b>',
            'Reason: '.$this->escape(filled($reason) ? $reason : 'Not given by the gateway'),
            'Customer: '.$this->escape($order->customer_name),
        ];

        $this->queue($lines);
    }

    protected function itemLine(OrderItem $item): string
    {
        return '• '.$this->escape($item->product_name)
            .' × '.$item->quantity
            .' — '.$this->escape(Money::format($item->line_total_kobo));
    }

    /** @param array<int, string> $lines */
    protected function queue(array $lines): void
    {
        SendTelegramMessage::dispatch(implode("\n", $lines));
    }

    protected function escape(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
