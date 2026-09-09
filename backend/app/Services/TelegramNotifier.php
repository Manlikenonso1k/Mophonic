<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramNotifier
{
    /**
     * Send a message to the configured group.
     *
     * Nothing here is allowed to surface: a notification failing is never a
     * reason to fail the order, the payment, or the queue worker.
     */
    public function send(string $message): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        $token = (string) config('telegram.bot_token');
        $baseUrl = rtrim((string) config('telegram.base_url'), '/');

        try {
            $response = Http::timeout(10)
                ->retry(2, 200)
                ->asJson()
                ->post("{$baseUrl}/bot{$token}/sendMessage", [
                    'chat_id' => config('telegram.chat_id'),
                    'text' => $message,
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true,
                ]);

            if (! $response->successful()) {
                Log::error('Telegram send failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (Throwable $e) {
            Log::error('Telegram send failed: '.$e->getMessage());

            return false;
        }
    }

    public function isConfigured(): bool
    {
        return (bool) config('telegram.enabled')
            && filled(config('telegram.bot_token'))
            && filled(config('telegram.chat_id'));
    }
}
