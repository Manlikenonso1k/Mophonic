<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ShopSeeder extends Seeder
{
    /**
     * Dummy catalogue. Prices are naira; the column stores kobo.
     *
     * @var array<int, array{name: string, accent: array{int, int, int}, products: array<int, array{string, int, string, string}>}>
     */
    protected array $catalogue = [
        [
            'name' => 'Snacks',
            'accent' => [214, 106, 46],
            'products' => [
                ['Chin Chin', 2500, '400g bag', 'Crisp fried cubes, cut thick and dusted with nutmeg.'],
                ['Plantain Chips', 1800, '250g bag', 'Hand-sliced ripe plantain, fried slow in groundnut oil.'],
                ['Kuli Kuli', 2200, '300g bag', 'Groundnut sticks pressed and fried the northern way.'],
                ['Coconut Candy', 2000, '250g bag', 'Toasted coconut set in caramelised sugar.'],
            ],
        ],
        [
            'name' => 'Spices & Blends',
            'accent' => [198, 66, 52],
            'products' => [
                ['Suya Spice', 3200, '200g jar', 'Yaji blend — groundnut, ginger, chilli, cloves. Heavy on the smoke.'],
                ['Pepper Soup Mix', 2800, '150g jar', 'Uda, uziza and calabash nutmeg, ground fresh.'],
                ['Dried Crayfish', 4500, '250g pack', 'Sun-dried and milled, for stews and soups.'],
                ['Curry & Thyme Rub', 2400, '200g jar', 'House blend for rice, grills and roast.'],
            ],
        ],
        [
            'name' => 'Sauces & Oils',
            'accent' => [176, 84, 40],
            'products' => [
                ['Ata Din Din', 5500, '500ml jar', 'Fried pepper sauce, deep and slow-cooked.'],
                ['Palm Oil', 6800, '1L bottle', 'Unrefined, cold-pressed, that heavy red colour.'],
                ['Groundnut Oil', 7200, '1L bottle', 'Pressed locally, nothing added.'],
                ['Shito Pepper Sauce', 4800, '350ml jar', 'Dried shrimp and chilli in oil. Use sparingly.'],
            ],
        ],
        [
            'name' => 'Grains & Staples',
            'accent' => [150, 122, 60],
            'products' => [
                ['Ofada Rice', 9500, '5kg bag', 'Unpolished short grain, grown and milled in Ogun.'],
                ['Garri Ijebu', 4200, '5kg bag', 'Fine and sour, the way it should be.'],
                ['Honey Beans', 8800, '5kg bag', 'Oloyin — sweet, soft-skinned, cooks down fast.'],
                ['Egusi Seeds', 6500, '1kg bag', 'Hulled melon seed, ready to mill.'],
            ],
        ],
        [
            'name' => 'Drinks',
            'accent' => [138, 62, 92],
            'products' => [
                ['Zobo Concentrate', 3800, '750ml bottle', 'Hibiscus steeped with ginger and pineapple.'],
                ['Kunu Aya', 3500, '750ml bottle', 'Tiger nut milk, spiced with dates and ginger.'],
                ['Chapman Mix', 4200, '1L bottle', 'The bitters-and-citrus base. Add soda and ice.'],
                ['Palm Wine', 5200, '1L bottle', 'Fresh tapped, bottled same day.'],
            ],
        ],
    ];

    public function run(): void
    {
        foreach ($this->catalogue as $categoryIndex => $group) {
            $category = Category::query()->updateOrCreate(
                ['slug' => Str::slug($group['name'])],
                [
                    'name' => $group['name'],
                    'sort_order' => $categoryIndex + 1,
                    'is_active' => true,
                ],
            );

            foreach ($group['products'] as $productIndex => [$name, $naira, $unit, $description]) {
                $slug = Str::slug($name);

                Product::query()->updateOrCreate(
                    ['slug' => $slug],
                    [
                        'category_id' => $category->id,
                        'name' => $name,
                        'description' => $description,
                        'price_kobo' => $naira * 100,
                        'unit_label' => $unit,
                        'image' => $this->placeholder($slug, $name, $group['accent'], $productIndex),
                        'stock' => 12 + ($productIndex * 7),
                        'sort_order' => $productIndex + 1,
                        'is_active' => true,
                    ],
                );
            }
        }
    }

    /**
     * Generate a dark, high-contrast placeholder so the shop design can be
     * judged before real photography exists.
     *
     * @param  array{int, int, int}  $accent
     */
    protected function placeholder(string $slug, string $name, array $accent, int $seed): string
    {
        $target = "products/{$slug}.jpg";

        if (Storage::disk('public')->exists($target)) {
            return $target;
        }

        $width = 900;
        $height = 1125;
        $canvas = imagecreatetruecolor($width, $height);

        // Ground: near-black with a slow vertical lift.
        for ($y = 0; $y < $height; $y++) {
            $lift = (int) round(6 + ($y / $height) * 10);
            $line = imagecolorallocate($canvas, $lift, $lift + 1, $lift + 1);
            imageline($canvas, 0, $y, $width, $y, $line);
        }

        // Accent glow, offset per product so the grid does not look stamped.
        $centreX = (int) ($width * (0.5 + (($seed % 3) - 1) * 0.08));
        $centreY = (int) ($height * (0.44 + ($seed % 2) * 0.06));
        $radius = (int) ($width * 0.62);

        for ($r = $radius; $r > 0; $r -= 3) {
            $falloff = 1 - ($r / $radius);
            $intensity = $falloff ** 2.4;
            $colour = imagecolorallocatealpha(
                $canvas,
                (int) round($accent[0] * $intensity),
                (int) round($accent[1] * $intensity),
                (int) round($accent[2] * $intensity),
                104,
            );
            imagefilledellipse($canvas, $centreX, $centreY, $r * 2, $r * 2, $colour);
        }

        // Geometric mark — a thin ring, cropped by the frame.
        $ring = imagecolorallocatealpha($canvas, 255, 255, 255, 96);
        $ringSize = (int) ($width * (0.52 + ($seed % 3) * 0.06));
        for ($t = 0; $t < 2; $t++) {
            imageellipse($canvas, $centreX, $centreY, $ringSize + $t, $ringSize + $t, $ring);
        }

        $this->label($canvas, $name, $width, $height);
        $this->grain($canvas, $width, $height);

        ob_start();
        imagejpeg($canvas, null, 86);
        $binary = (string) ob_get_clean();
        imagedestroy($canvas);

        Storage::disk('public')->put($target, $binary);

        return $target;
    }

    /** Set the product name in the image, when a usable TTF is on the box. */
    protected function label(\GdImage $canvas, string $name, int $width, int $height): void
    {
        $font = $this->font();

        if ($font === null) {
            return;
        }

        $text = Str::upper($name);
        $size = 34;
        $box = imagettfbbox($size, 0, $font, $text);
        $textWidth = abs($box[4] - $box[0]);

        $ink = imagecolorallocatealpha($canvas, 244, 244, 244, 24);
        imagettftext(
            $canvas,
            $size,
            0,
            (int) (($width - $textWidth) / 2),
            (int) ($height * 0.88),
            $ink,
            $font,
            $text,
        );
    }

    protected function font(): ?string
    {
        $candidates = [
            'C:\\Windows\\Fonts\\arialbd.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
            '/System/Library/Fonts/Supplemental/Arial Bold.ttf',
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate) && function_exists('imagettftext')) {
                return $candidate;
            }
        }

        return null;
    }

    protected function grain(\GdImage $canvas, int $width, int $height): void
    {
        for ($i = 0; $i < 9000; $i++) {
            $value = random_int(120, 210);
            $speck = imagecolorallocatealpha($canvas, $value, $value, $value, 118);
            imagesetpixel($canvas, random_int(0, $width - 1), random_int(0, $height - 1), $speck);
        }
    }
}
