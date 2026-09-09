<?php

namespace Tests\Feature;

use App\Filament\Pages\ManageSiteSettings;
use App\Models\SiteSetting;
use App\Models\Work;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_the_login_screen(): void
    {
        $this->get('/admin/works')->assertRedirect('/admin/login');
    }

    public function test_an_admin_can_reach_every_panel_screen(): void
    {
        $admin = $this->superAdminUser();

        $work = Work::create([
            'title' => 'K-POP Video',
            'slug' => 'k-pop-video',
            'type' => 'video',
            'sort_order' => 1,
        ]);

        $this->actingAs($admin);

        $this->get('/admin')->assertOk();
        $this->get('/admin/works')->assertOk()->assertSee('K-POP Video');
        $this->get('/admin/works/create')->assertOk();
        $this->get("/admin/works/{$work->id}/edit")->assertOk();
        $this->get('/admin/subscribers')->assertOk();
        $this->get('/admin/manage-site-settings')->assertOk();
    }

    public function test_an_admin_can_save_the_site_settings(): void
    {
        $this->actingAs($this->superAdminUser());

        Livewire::test(ManageSiteSettings::class)
            ->fillForm([
                'eyebrow' => 'DISCOVER',
                'heading' => 'MOPHONIK II',
                'menu_items' => [
                    ['label' => 'Shop', 'url' => 'https://example.com', 'external' => true],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = SiteSetting::current();

        $this->assertSame('DISCOVER', $settings->eyebrow);
        $this->assertSame('MOPHONIK II', $settings->heading);
        $this->assertSame('Shop', $settings->menu_items[0]['label']);
    }
}
