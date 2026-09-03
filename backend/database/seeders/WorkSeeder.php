<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use App\Models\Work;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class WorkSeeder extends Seeder
{
    /**
     * The original Mophonik line-up. Every cover and background video is
     * copied out of the scraped `assets/` folder into the public disk, so the
     * admin panel can replace any of them later.
     *
     * @var array<int, array{title: string, slug: string, type: string, cover: string, video: string}>
     */
    protected array $works = [
        ['title' => '4X4 Video', 'slug' => '4x4-video', 'type' => 'video', 'cover' => 'b99258e29844da040e97c3da70c31bbb56d0a27e-1250x1250.jpg', 'video' => 'ad0889c069f94812b426486c264dd7b3.mp4'],
        ['title' => 'FE!N Video', 'slug' => 'fein-video', 'type' => 'video', 'cover' => '8d32988e8743bbe77099f1ac958656d4de27ab65-2000x2428.jpg', 'video' => 'f964089c84a240dfa7af199106281137.mp4'],
        ['title' => 'I KNOW ? Video', 'slug' => 'i-know-video', 'type' => 'video', 'cover' => '335ffdd7c010215536c256168e7299ecc0175b84-2000x2428.jpg', 'video' => '6bfa2ad3f7fe43adb8acf935c8a81c66.mp4'],
        ['title' => 'Topia Twins Video', 'slug' => 'topia-twins', 'type' => 'video', 'cover' => '101a666b69e2f25125dc3b55dc6ce80fee7179c2-2000x2428.jpg', 'video' => 'ded0af90dd0747d584ef9e6c39074fca.mp4'],
        ['title' => 'Circus Maximus - The Film', 'slug' => 'circus-maximus', 'type' => 'film', 'cover' => '020a31e1e643a56233be6b9d0fbd3338355d4374-500x605.png', 'video' => '8b880a2fd72e496094885d530a75526e.mp4'],
        ['title' => 'Mophonik Zine', 'slug' => 'zine', 'type' => 'zine', 'cover' => 'd68aa0c4a3fc734b1048b82fb4a60de851a36b19-999x1212.png', 'video' => '7b03f29179604cb880dc2460304409f0.mp4'],
        ['title' => 'SIRENS Video', 'slug' => 'sirens-video', 'type' => 'video', 'cover' => '67406b19341f0e39dd87088daf422a1c11b46f50-2000x2428.jpg', 'video' => 'a7349cb8500649c18a8229758226eff5.mp4'],
        ['title' => 'DELRESTO Video', 'slug' => 'delresto-video', 'type' => 'video', 'cover' => '8460c7c70ff968fbed3016cfa405199bd5edde81-2000x2428.jpg', 'video' => 'a487dd6d7ca44b9bb18de92bf6b4ca65.mp4'],
        ['title' => 'HYAENA Video', 'slug' => 'hyaena-video', 'type' => 'video', 'cover' => 'd93406a8e08682a5ad3f1194ebb9fb47d9999a9a-2000x2428.jpg', 'video' => '9a8a1e01418448fdba0261939c70262f.mp4'],
        ['title' => 'Modern Jam Video', 'slug' => 'modern-jam-video', 'type' => 'video', 'cover' => '21f5deca90b412f1135ca527505050a625aa0a0b-2000x2428.jpg', 'video' => '5eee4214cd514748a159946c15d7c7da.mp4'],
        ['title' => "God's Country Video", 'slug' => 'gods-country-video', 'type' => 'video', 'cover' => 'c134c13cbcff06da7e8302c96b69638704112c07-2000x2428.jpg', 'video' => 'c2db8b6c858e4562a31e8bf7597d5e6e.mp4'],
        ['title' => 'K-POP Video', 'slug' => 'k-pop-video', 'type' => 'video', 'cover' => 'b38b5aead7d016c1b91817d16a0f3a6f985fed5e-1000x1210.png', 'video' => '79161616355e4a28a138992e23a63b53.mp4'],
        ['title' => 'Live In Pompeii', 'slug' => 'live-in-pompeii', 'type' => 'film', 'cover' => 'f802323db539415d17f5112b9407ba5309b9fa97-998x1212.jpg', 'video' => '146dc5c1e98c4529921db41f58315bf3.mp4'],
    ];

    public function run(): void
    {
        SiteSetting::current();

        $source = realpath(base_path('../assets')) ?: base_path('../assets');

        foreach ($this->works as $index => $work) {
            Work::query()->updateOrCreate(
                ['slug' => $work['slug']],
                [
                    'title' => $work['title'],
                    'type' => $work['type'],
                    'cover_image' => $this->copy($source, $work['cover'], 'works/covers'),
                    'background_video' => $this->copy($source, $work['video'], 'works/videos'),
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ],
            );
        }
    }

    /** Copy an asset onto the public disk and return its relative path. */
    protected function copy(string $source, string $file, string $directory): ?string
    {
        $from = $source.DIRECTORY_SEPARATOR.$file;
        $target = $directory.'/'.$file;

        if (! File::exists($from)) {
            $this->command?->warn("Missing asset: {$from}");

            return null;
        }

        if (! Storage::disk('public')->exists($target)) {
            Storage::disk('public')->put($target, File::get($from));
        }

        return $target;
    }
}
