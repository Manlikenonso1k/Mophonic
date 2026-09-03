<?php

namespace Tests\Feature;

use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ShopAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_see_orders(): void
    {
        $this->get('/admin/orders')->assertRedirect('/admin/login');
    }

    public function test_guests_cannot_see_products_or_categories(): void
    {
        $this->get('/admin/products')->assertRedirect('/admin/login');
        $this->get('/admin/categories')->assertRedirect('/admin/login');
    }

    public function test_an_admin_can_reach_every_shop_screen(): void
    {
        $this->actingAs(User::factory()->create());

        $category = Category::factory()->create(['name' => 'Snacks']);
        $product = Product::factory()->for($category)->create(['name' => 'Chin Chin']);
        $order = Order::factory()->create(['reference' => 'SHOP-ADMIN-1']);
        OrderItem::factory()->for($order)->for($product)->create(['product_name' => 'Chin Chin']);

        $this->get('/admin')->assertOk();   // dashboard, with the shop widgets
        $this->get('/admin/products')->assertOk()->assertSee('Chin Chin');
        $this->get('/admin/products/create')->assertOk();
        $this->get("/admin/products/{$product->id}/edit")->assertOk();
        $this->get('/admin/categories')->assertOk()->assertSee('Snacks');
        $this->get("/admin/categories/{$category->id}/edit")->assertOk();
        $this->get('/admin/orders')->assertOk()->assertSee('SHOP-ADMIN-1');
        $this->get("/admin/orders/{$order->id}")->assertOk()->assertSee('Chin Chin');
        $this->get("/admin/orders/{$order->id}/edit")->assertOk();
    }

    public function test_orders_cannot_be_created_by_hand(): void
    {
        $this->actingAs(User::factory()->create());

        Order::factory()->create(['reference' => 'SHOP-EXISTING-1']);

        $this->get('/admin/orders/create')->assertNotFound();

        // …and the list screen must not offer a button that leads nowhere.
        $this->get('/admin/orders')
            ->assertOk()
            ->assertDontSee('New order', escape: false);
    }

    public function test_the_order_form_exposes_no_line_items_or_totals(): void
    {
        $this->actingAs(User::factory()->create());

        $order = Order::factory()->create();
        OrderItem::factory()->for($order)->create();

        Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
            ->assertFormFieldExists('status')
            ->assertFormFieldDoesNotExist('items')
            ->assertFormFieldDoesNotExist('total_kobo')
            ->assertFormFieldDoesNotExist('subtotal_kobo');
    }

    public function test_editing_an_order_changes_fulfilment_but_not_its_contents(): void
    {
        $this->actingAs(User::factory()->create());

        $order = Order::factory()->create(['status' => 'new', 'total_kobo' => 500000]);
        $item = OrderItem::factory()->for($order)->create([
            'product_name' => 'Ofada Rice',
            'quantity' => 2,
            'unit_price_kobo' => 250000,
            'line_total_kobo' => 500000,
        ]);

        Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
            ->fillForm(['status' => 'preparing'])
            ->call('save')
            ->assertHasNoFormErrors();

        $order->refresh();

        $this->assertSame('preparing', $order->status);
        $this->assertSame(500000, $order->total_kobo);
        $this->assertSame('Ofada Rice', $item->refresh()->product_name);
        $this->assertSame(2, $item->quantity);
    }

    public function test_an_admin_can_create_a_product_priced_in_naira(): void
    {
        $this->actingAs(User::factory()->create());

        $category = Category::factory()->create();

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Suya Spice',
                'slug' => 'suya-spice',
                'category_id' => $category->id,
                'price_kobo' => 3200,      // naira in the form
                'stock' => 20,
                'sort_order' => 1,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::query()->where('slug', 'suya-spice')->firstOrFail();

        $this->assertSame(320000, $product->price_kobo);   // stored as kobo
    }
}
