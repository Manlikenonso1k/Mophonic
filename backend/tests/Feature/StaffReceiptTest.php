<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffReceiptTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    protected function order(): Order
    {
        $order = Order::factory()->create([
            'reference' => 'SHOP-STAFF-1',
            'payment_reference' => 'SHOP-STAFF-1',
            'payment_status' => 'paid',
            'paid_at' => now(),
        ]);

        OrderItem::factory()->for($order)->create(['product_name' => 'Suya Spice']);

        return $order;
    }

    public function test_a_waiter_can_view_an_order_receipt(): void
    {
        $order = $this->order();

        $this->actingAs(User::factory()->create()->assignRole(User::WAITER));

        $this->get("/admin/orders/{$order->id}/receipt")
            ->assertOk()
            ->assertSee($order->reference)
            ->assertSee('Suya Spice');
    }

    public function test_a_waiter_still_cannot_edit_the_order(): void
    {
        $order = $this->order();

        $this->actingAs(User::factory()->create()->assignRole(User::WAITER));

        $this->get("/admin/orders/{$order->id}/edit")->assertForbidden();
    }

    public function test_a_super_admin_can_view_the_receipt(): void
    {
        $order = $this->order();

        $this->actingAs(User::factory()->create()->assignRole(User::SUPER_ADMIN));

        $this->get("/admin/orders/{$order->id}/receipt")->assertOk();
    }

    public function test_an_account_without_the_permission_is_refused(): void
    {
        $order = $this->order();

        $user = User::factory()->create()->assignRole(User::WAITER);
        $user->revokePermissionTo('view_order_receipt');
        $user->roles()->first()->revokePermissionTo('view_order_receipt');

        $this->actingAs($user->fresh());

        $this->get("/admin/orders/{$order->id}/receipt")->assertForbidden();
    }

    public function test_the_staff_receipt_shows_the_internal_fields(): void
    {
        $order = $this->order();

        $this->actingAs(User::factory()->create()->assignRole(User::SUPER_ADMIN));

        $this->get("/admin/orders/{$order->id}/receipt")
            ->assertOk()
            ->assertSee($order->customer_email)
            ->assertSee('Gateway ref', escape: false);
    }
}
