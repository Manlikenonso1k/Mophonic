<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Sample orders, so the dashboard and the orders screen have something to
 * show. Deliberately NOT wired into DatabaseSeeder — run it by hand:
 * `php artisan db:seed --class=DemoOrderSeeder`.
 */
class DemoOrderSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::query()->active()->get();

        if ($products->isEmpty()) {
            $this->command?->warn('Seed the catalogue first.');

            return;
        }

        $customers = [
            ['Ada Obi', 'ada.obi@example.com', '08031122334', '14 Bode Thomas, Surulere, Lagos'],
            ['Tunde Bello', 'tunde.bello@example.com', '08065544332', '3 Awolowo Road, Ikoyi, Lagos'],
            ['Ngozi Eze', 'ngozi.eze@example.com', '07011223344', '22 Aba Road, Port Harcourt'],
            ['Musa Danjuma', 'musa.d@example.com', '08099887766', '9 Ahmadu Bello Way, Kaduna'],
            ['Chidi Okafor', 'chidi.okafor@example.com', '08122334455', '7 Zik Avenue, Enugu'],
            ['Fatima Sule', 'fatima.sule@example.com', '08033445566', '18 Yakubu Gowon Cres, Abuja'],
        ];

        $plan = [
            ['status' => 'new', 'paid' => true, 'daysAgo' => 0],
            ['status' => 'preparing', 'paid' => true, 'daysAgo' => 1],
            ['status' => 'shipped', 'paid' => true, 'daysAgo' => 3],
            ['status' => 'delivered', 'paid' => true, 'daysAgo' => 6],
            ['status' => 'delivered', 'paid' => true, 'daysAgo' => 11],
            ['status' => 'pending_payment', 'paid' => false, 'daysAgo' => 0],
        ];

        foreach ($plan as $index => $step) {
            [$name, $email, $phone, $address] = $customers[$index];

            $picks = $products->random(min(3, $products->count()));
            $subtotal = 0;
            $lines = [];

            foreach ($picks as $offset => $product) {
                $quantity = 1 + (($index + $offset) % 3);
                $lineTotal = $product->price_kobo * $quantity;
                $subtotal += $lineTotal;

                $lines[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit_price_kobo' => $product->price_kobo,
                    'quantity' => $quantity,
                    'line_total_kobo' => $lineTotal,
                ];
            }

            $delivery = $index % 2 === 0 ? 150000 : 0;
            $placedAt = now()->subDays($step['daysAgo'])->subHours($index);

            $order = Order::query()->create([
                'reference' => 'SHOP-'.strtoupper(Str::random(12)).'-'.$placedAt->timestamp,
                'customer_name' => $name,
                'customer_email' => $email,
                'customer_phone' => $phone,
                'delivery_address' => $address,
                'notes' => $index === 1 ? 'Please call before delivery.' : null,
                'subtotal_kobo' => $subtotal,
                'delivery_fee_kobo' => $delivery,
                'total_kobo' => $subtotal + $delivery,
                'status' => $step['status'],
                'payment_status' => $step['paid'] ? 'paid' : 'pending',
                'payment_reference' => null,
                'paid_at' => $step['paid'] ? $placedAt : null,
                'created_at' => $placedAt,
                'updated_at' => $placedAt,
            ]);

            $order->items()->createMany($lines);
        }

        $this->command?->info('Seeded '.count($plan).' demo orders.');
    }
}
