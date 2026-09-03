<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Order> */
class OrderFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $subtotal = fake()->numberBetween(30, 400) * 100 * 100;
        $delivery = fake()->randomElement([0, 150000]);

        return [
            'reference' => 'SHOP-'.strtoupper(Str::random(12)).'-'.fake()->unixTime(),
            'customer_name' => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'customer_phone' => fake()->numerify('080########'),
            'delivery_address' => fake()->address(),
            'notes' => null,
            'subtotal_kobo' => $subtotal,
            'delivery_fee_kobo' => $delivery,
            'total_kobo' => $subtotal + $delivery,
            'status' => 'new',
            'payment_status' => 'paid',
            'payment_reference' => null,
            'paid_at' => now(),
        ];
    }

    public function awaitingPayment(): static
    {
        return $this->state(fn () => [
            'status' => 'pending_payment',
            'payment_status' => 'pending',
            'paid_at' => null,
        ]);
    }

    public function status(string $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
