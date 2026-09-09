<?php

namespace Tests\Feature;

use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    protected function waiter(): User
    {
        return User::factory()->create()->assignRole(User::WAITER);
    }

    protected function superAdmin(): User
    {
        return User::factory()->create()->assignRole(User::SUPER_ADMIN);
    }

    public function test_a_waiter_cannot_reach_the_user_list(): void
    {
        $this->actingAs($this->waiter());

        $this->get('/admin/users')->assertForbidden();
    }

    public function test_a_super_admin_can_reach_the_user_list(): void
    {
        $this->actingAs($this->superAdmin());

        $this->get('/admin/users')->assertOk();
    }

    public function test_a_waiter_cannot_edit_an_order_but_can_view_one(): void
    {
        $order = Order::factory()->create();

        $this->actingAs($this->waiter());

        $this->get("/admin/orders/{$order->id}/edit")->assertForbidden();
        $this->get("/admin/orders/{$order->id}")->assertOk();
        $this->get('/admin/orders')->assertOk();
    }

    public function test_a_super_admin_can_edit_an_order(): void
    {
        $order = Order::factory()->create();

        $this->actingAs($this->superAdmin());

        $this->get("/admin/orders/{$order->id}/edit")->assertOk();
    }

    public function test_a_waiter_can_update_a_product_image(): void
    {
        Storage::fake('public');

        $product = Product::factory()->create(['price_kobo' => 250000]);

        $this->actingAs($this->waiter());

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm(['image' => [UploadedFile::fake()->image('new.jpg')]])
            ->call('save')
            ->assertHasNoFormErrors();

        $product->refresh();

        $this->assertNotNull($product->image);
        Storage::disk('public')->assertExists($product->image);
    }

    public function test_a_waiter_cannot_change_a_price_even_by_submitting_one(): void
    {
        $product = Product::factory()->create(['price_kobo' => 250000, 'name' => 'Suya Spice']);

        $this->actingAs($this->waiter());

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm(['price_kobo' => 1, 'name' => 'Free Suya'])
            ->call('save');

        $product->refresh();

        // The fields are disabled, so they are never dehydrated into the model.
        $this->assertSame(250000, $product->price_kobo);
        $this->assertSame('Suya Spice', $product->name);
    }

    public function test_a_super_admin_can_change_a_price(): void
    {
        $product = Product::factory()->create(['price_kobo' => 250000]);

        $this->actingAs($this->superAdmin());

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm(['price_kobo' => 3000])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(300000, $product->refresh()->price_kobo);
    }

    public function test_the_last_super_admin_cannot_be_demoted(): void
    {
        $lastSuperAdmin = $this->superAdmin();

        // Someone who may manage users without being a super admin themselves —
        // the only actor who could otherwise strip the final super admin.
        $manager = User::factory()->create()->assignRole(User::WAITER);
        $manager->givePermissionTo(['view_any_user', 'view_user', 'update_user']);

        $this->assertTrue($lastSuperAdmin->fresh()->isLastSuperAdmin());

        $this->actingAs($manager);

        Livewire::test(EditUser::class, ['record' => $lastSuperAdmin->getRouteKey()])
            ->fillForm(['roles' => [Role::findByName(User::WAITER)->getKey()]])
            ->call('save');

        $this->assertTrue($lastSuperAdmin->fresh()->hasRole(User::SUPER_ADMIN));
    }

    public function test_a_user_cannot_change_their_own_role(): void
    {
        $admin = $this->superAdmin();
        $this->superAdmin(); // so the one being edited is not the last

        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $admin->getRouteKey()])
            ->fillForm(['roles' => [Role::findByName(User::WAITER)->getKey()]])
            ->call('save');

        $this->assertTrue($admin->fresh()->hasRole(User::SUPER_ADMIN));
    }

    public function test_a_user_without_a_role_cannot_reach_the_panel_at_all(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/admin')->assertForbidden();
    }

    public function test_a_waiter_sees_no_settings_or_revenue(): void
    {
        $this->actingAs($this->waiter());

        $this->get('/admin/manage-site-settings')->assertForbidden();
        $this->get('/admin/subscribers')->assertForbidden();
    }

    public function test_seeding_without_a_configured_email_still_leaves_a_way_in(): void
    {
        config(['admin.super_admin_email' => null, 'admin.fallback_admin_email' => null]);

        $first = User::factory()->create();
        User::factory()->create();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertTrue($first->fresh()->hasRole(User::SUPER_ADMIN));
    }

    public function test_it_falls_back_to_the_admin_email_when_the_super_email_is_unset(): void
    {
        $intended = User::factory()->create(['email' => 'owner@example.com']);
        User::factory()->create();

        config(['admin.super_admin_email' => null, 'admin.fallback_admin_email' => 'owner@example.com']);

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertTrue($intended->fresh()->hasRole(User::SUPER_ADMIN));
    }

    public function test_it_does_not_promote_anyone_when_a_super_admin_already_exists(): void
    {
        $existing = $this->superAdmin();
        $other = User::factory()->create();

        config(['admin.super_admin_email' => null, 'admin.fallback_admin_email' => null]);

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertTrue($existing->fresh()->hasRole(User::SUPER_ADMIN));
        $this->assertFalse($other->fresh()->hasRole(User::SUPER_ADMIN));
    }
}
