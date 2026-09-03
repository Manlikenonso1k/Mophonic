<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_visible_products_in_order(): void
    {
        $category = Category::factory()->create(['name' => 'Snacks', 'slug' => 'snacks']);

        Product::factory()->for($category)->create([
            'name' => 'Second',
            'slug' => 'second',
            'sort_order' => 2,
        ]);
        Product::factory()->for($category)->create([
            'name' => 'First',
            'slug' => 'first',
            'sort_order' => 1,
            'price_kobo' => 250000,
        ]);
        Product::factory()->for($category)->hidden()->create([
            'name' => 'Hidden',
            'slug' => 'hidden',
            'sort_order' => 0,
        ]);

        $this->getJson('/api/shop')
            ->assertOk()
            ->assertJsonCount(1, 'categories')
            ->assertJsonCount(2, 'products')
            ->assertJsonPath('products.0.name', 'First')
            ->assertJsonPath('products.0.priceKobo', 250000)
            ->assertJsonPath('products.0.category', 'snacks')
            ->assertJsonPath('products.1.name', 'Second');
    }

    public function test_it_hides_products_whose_category_is_inactive_from_the_category_list(): void
    {
        Category::factory()->hidden()->create(['name' => 'Retired', 'slug' => 'retired']);

        $this->getJson('/api/shop')
            ->assertOk()
            ->assertJsonCount(0, 'categories');
    }

    public function test_it_returns_a_single_product(): void
    {
        Product::factory()->create(['name' => 'Suya Spice', 'slug' => 'suya-spice']);

        $this->getJson('/api/shop/products/suya-spice')
            ->assertOk()
            ->assertJsonPath('product.name', 'Suya Spice');
    }

    public function test_a_hidden_product_is_not_reachable_directly(): void
    {
        Product::factory()->hidden()->create(['slug' => 'secret-sauce']);

        $this->getJson('/api/shop/products/secret-sauce')->assertNotFound();
    }

    public function test_the_delivery_fee_comes_from_site_settings(): void
    {
        SiteSetting::current()->update(['delivery_fee_kobo' => 150000]);

        $this->getJson('/api/shop')
            ->assertOk()
            ->assertJsonPath('deliveryFee', 150000);
    }
}
