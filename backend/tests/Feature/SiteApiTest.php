<?php

namespace Tests\Feature;

use App\Models\Subscriber;
use App\Models\Work;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_active_works_in_order(): void
    {
        Work::create([
            'title' => 'Second',
            'slug' => 'second',
            'type' => 'video',
            'cover_url' => 'https://example.com/second.jpg',
            'sort_order' => 2,
        ]);

        Work::create([
            'title' => 'First',
            'slug' => 'first',
            'type' => 'album',
            'cover_image' => 'works/covers/first.jpg',
            'background_video_url' => 'https://example.com/first.mp4',
            'sort_order' => 1,
        ]);

        Work::create([
            'title' => 'Hidden',
            'slug' => 'hidden',
            'type' => 'video',
            'sort_order' => 0,
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/site');

        $response->assertOk()
            ->assertJsonPath('settings.heading', 'MOPHONIK')
            ->assertJsonCount(2, 'works')
            ->assertJsonPath('works.0.title', 'First')
            ->assertJsonPath('works.0.href', '/mophonik/first')
            ->assertJsonPath('works.1.title', 'Second');

        $this->assertStringContainsString(
            '/storage/works/covers/first.jpg',
            $response->json('works.0.cover'),
        );
    }

    public function test_an_uploaded_file_wins_over_the_url(): void
    {
        $work = new Work([
            'cover_image' => 'works/covers/upload.jpg',
            'cover_url' => 'https://example.com/remote.jpg',
        ]);

        $this->assertStringContainsString('/storage/works/covers/upload.jpg', $work->coverSrc());
    }

    public function test_it_stores_a_subscriber(): void
    {
        $this->postJson('/api/subscribe', ['email' => 'Fan@Example.com', 'terms' => true])
            ->assertOk();

        $this->assertDatabaseHas(Subscriber::class, ['email' => 'fan@example.com']);
    }

    public function test_it_rejects_an_unchecked_terms_box(): void
    {
        $this->postJson('/api/subscribe', ['email' => 'fan@example.com', 'terms' => false])
            ->assertStatus(422)
            ->assertJsonValidationErrors('terms');
    }
}
