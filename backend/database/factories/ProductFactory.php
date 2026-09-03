<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'category_id' => Category::factory(),
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'description' => fake()->paragraph(),
            'price_kobo' => fake()->numberBetween(50, 900) * 100 * 10,
            'image_url' => null,
            'unit_label' => fake()->randomElement(['250g', '500g', '1kg', 'Pack of 6']),
            'stock' => fake()->numberBetween(1, 60),
            'sort_order' => fake()->numberBetween(1, 50),
            'is_active' => true,
        ];
    }

    public function hidden(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn () => ['stock' => 0]);
    }
}
