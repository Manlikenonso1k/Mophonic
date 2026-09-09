<?php

namespace App\Jobs;

use App\Services\TelegramNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Keeps the Telegram API off the request path — checkout returns without
 * waiting on it.
 */
class SendTelegramMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(public readonly string $message) {}

    public function handle(TelegramNotifier $notifier): void
    {
        $notifier->send($this->message);
    }
}
