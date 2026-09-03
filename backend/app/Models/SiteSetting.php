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
        'delivery_fee_kobo',
        'menu_items',
    ];

    protected $casts = [
        'menu_items' => 'array',
        'delivery_fee_kobo' => 'integer',
    ];

    /** The site runs on a single settings row. */
    public static function current(): self
    {
        return static::query()->firstOrCreate(['id' => 1], [
            'eyebrow' => 'EXPLORE',
            'heading' => 'MOPHONIK',
            'shop_url' => '/shop',
            'terms_url' => '/terms',
            'newsletter_heading' => 'Enter email for updates',
            'delivery_fee_kobo' => 0,
            'menu_items' => self::defaultMenuItems(),
        ]);
    }

    /** @return array<int, array{label: string, url: string, external: bool}> */
    public static function defaultMenuItems(): array
    {
        return [
            ['label' => 'Shop', 'url' => '/shop', 'external' => false],
            ['label' => 'Tour', 'url' => '/tour', 'external' => false],
            ['label' => 'Mophonik', 'url' => '/', 'external' => false],
            ['label' => 'Mophonik Album', 'url' => '/explore', 'external' => false],
        ];
    }
}
