<?php

namespace App\Services;

use App\Models\Product;
use App\Models\SiteSetting;
use Illuminate\Validation\ValidationException;

class CartPricer
{
    /**
     * Price a cart from its product ids alone. The browser never gets to say
     * what anything costs — every figure below comes out of the price table.
     *
     * @param  array<int, array{product_id: int|string, quantity: int|string}>  $lines
     * @return array{items: array<int, array{product_id: int, product_name: string, unit_price_kobo: int, quantity: int, line_total_kobo: int}>, subtotal_kobo: int, delivery_fee_kobo: int, total_kobo: int}
     */
    public function price(array $lines): array
    {
        $quantities = [];

        foreach ($lines as $line) {
            $productId = (int) ($line['product_id'] ?? 0);
            $quantity = (int) ($line['quantity'] ?? 0);

            if ($productId <= 0 || $quantity <= 0) {
                continue;
            }

            // The same product can arrive twice; treat that as one line.
            $quantities[$productId] = ($quantities[$productId] ?? 0) + $quantity;
        }

        if ($quantities === []) {
            throw ValidationException::withMessages(['items' => 'Your cart is empty.']);
        }

        $products = Product::query()
            ->active()
            ->whereIn('id', array_keys($quantities))
            ->get()
            ->keyBy('id');

        $items = [];
        $subtotal = 0;

        foreach ($quantities as $productId => $quantity) {
            $product = $products->get($productId);

            if (! $product instanceof Product) {
                throw ValidationException::withMessages([
                    'items' => 'One of the products is no longer available.',
                ]);
            }

            if ($product->price_kobo <= 0) {
                throw ValidationException::withMessages([
                    'items' => "{$product->name} has no price set.",
                ]);
            }

            if ($product->stock < $quantity) {
                throw ValidationException::withMessages([
                    'items' => "Only {$product->stock} × {$product->name} left in stock.",
                ]);
            }

            $lineTotal = $product->price_kobo * $quantity;
            $subtotal += $lineTotal;

            $items[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'unit_price_kobo' => $product->price_kobo,
                'quantity' => $quantity,
                'line_total_kobo' => $lineTotal,
            ];
        }

        $delivery = (int) SiteSetting::current()->delivery_fee_kobo;

        return [
            'items' => $items,
            'subtotal_kobo' => $subtotal,
            'delivery_fee_kobo' => $delivery,
            'total_kobo' => $subtotal + $delivery,
        ];
    }
}
