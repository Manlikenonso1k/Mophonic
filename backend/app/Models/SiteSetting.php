<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = [
        'eyebrow',
        'heading',
        'shop_url',
        'terms_url',
        'newsletter_heading',
        'menu_items',
    ];

    protected $casts = [
        'menu_items' => 'array',
    ];

    /** The site runs on a single settings row. */
    public static function current(): self
    {
        return static::query()->firstOrCreate(['id' => 1], [
            'eyebrow' => 'EXPLORE',
            'heading' => 'MOPHONIK',
            'shop_url' => 'https://shop.travisscott.com/',
            'terms_url' => 'https://shop.travisscott.com/pages/terms',
            'newsletter_heading' => 'Enter email for updates',
            'menu_items' => self::defaultMenuItems(),
        ]);
    }

    public static function defaultMenuItems(): array
    {
        return [
            ['label' => 'Shop', 'url' => 'https://shop.travisscott.com/', 'external' => true],
            ['label' => 'Tour', 'url' => '/tour', 'external' => false],
            ['label' => 'Mophonik', 'url' => '/', 'external' => false],
            ['label' => 'Mophonik Album', 'url' => '/explore', 'external' => false],
        ];
    }
}
