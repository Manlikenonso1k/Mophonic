<?php

namespace Tests\Feature;

use App\Filament\Resources\Works\Pages\EditWork;
use App\Models\Work;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ReplaceArtworkTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_editor_can_replace_the_cover_and_the_video(): void
    {
        Storage::fake('public');

        $this->actingAs($this->superAdminUser());

        $work = Work::create([
            'title' => 'K-POP Video',
            'slug' => 'k-pop-video',
            'type' => 'video',
            'cover_url' => 'https://example.com/old-cover.jpg',
            'sort_order' => 1,
        ]);

        Livewire::test(EditWork::class, ['record' => $work->getRouteKey()])
            ->fillForm([
                'cover_image' => [UploadedFile::fake()->image('new-cover.jpg')],
                'background_video' => [UploadedFile::fake()->create('new-video.mp4', 512, 'video/mp4')],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $work->refresh();

        $this->assertNotNull($work->cover_image);
        $this->assertNotNull($work->background_video);
        Storage::disk('public')->assertExists($work->cover_image);
        Storage::disk('public')->assertExists($work->background_video);

        // The uploaded file must take over from the URL the record had before.
        $this->assertStringContainsString($work->cover_image, $work->coverSrc());

        $this->getJson('/api/site')
            ->assertOk()
            ->assertJsonPath('works.0.title', 'K-POP Video');
    }
}
