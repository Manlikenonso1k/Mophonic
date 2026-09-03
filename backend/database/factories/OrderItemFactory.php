<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OrderItem> */
class OrderItemFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $unitPrice = fake()->numberBetween(10, 120) * 100 * 100;
        $quantity = fake()->numberBetween(1, 4);

        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'product_name' => fake()->words(3, true),
            'unit_price_kobo' => $unitPrice,
            'quantity' => $quantity,
            'line_total_kobo' => $unitPrice * $quantity,
        ];
    }
}
