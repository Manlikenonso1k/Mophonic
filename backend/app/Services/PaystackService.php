<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class PaystackService
{
    private string $secretKey;

    private string $baseUrl;

    public function __construct()
    {
        $this->secretKey = (string) config('services.paystack.secret_key');
        $this->baseUrl = rtrim((string) config('services.paystack.base_url', 'https://api.paystack.co'), '/');
    }

    /** Lets checkout degrade safely while the keys are still placeholders. */
    public function isConfigured(): bool
    {
        return $this->secretKey !== ''
            && ! str_contains($this->secretKey, 'your_paystack')
            && str_starts_with($this->secretKey, 'sk_');
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    public function initialize(
        string $email,
        int $amountKobo,
        string $reference,
        string $callbackUrl,
        array $metadata = [],
    ): array {
        $response = Http::withToken($this->secretKey)->acceptJson()
            ->post($this->baseUrl.'/transaction/initialize', [
                'email' => $email,
                'amount' => $amountKobo,
                'reference' => $reference,
                'callback_url' => $callbackUrl,
                'metadata' => $metadata,
                'channels' => ['card', 'bank', 'ussd', 'qr', 'mobile_money', 'bank_transfer'],
            ]);

        if (! $response->successful() || $response->json('status') !== true) {
            throw new RuntimeException('Paystack init failed: '.($response->json('message') ?? 'unknown'));
        }

        return (array) $response->json('data');
    }

    /** @return array<string, mixed> */
    public function verify(string $reference): array
    {
        $response = Http::withToken($this->secretKey)->acceptJson()
            ->get($this->baseUrl.'/transaction/verify/'.rawurlencode($reference));

        if (! $response->successful()) {
            throw new RuntimeException('Paystack verify failed');
        }

        return (array) $response->json('data');
    }

    /** SHA-512 over the raw body, compared in constant time. */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        if ($signature === '' || ! $this->isConfigured()) {
            return false;
        }

        return hash_equals(hash_hmac('sha512', $payload, $this->secretKey), $signature);
    }

    /** Prefixed so one webhook endpoint can dispatch by feature. */
    public function generateReference(string $prefix = 'SHOP'): string
    {
        return $prefix.'-'.strtoupper(Str::random(12)).'-'.time();
    }
}
