<?php

namespace App\Support;

/**
 * The permission catalogue.
 *
 * Kept in one place so the seeder, the Filament resources and the tests cannot
 * drift apart — a typo in a resource guard would otherwise fail closed and
 * silently hide a screen.
 */
class Permissions
{
    /** @return array<int, string> */
    public static function all(): array
    {
        return array_merge(
            self::crud('order'),
            self::crud('product'),
            self::images('product'),
            self::crud('category'),
            self::crud('work'),
            self::images('work'),
            self::crud('subscriber'),
            self::crud('user'),
            [
                'assign_role',
                'view_site_settings',
                'update_site_settings',
                'view_revenue',
                'view_order_receipt',
            ],
        );
    }

    /**
     * What a waiter gets: the order book, and the pictures on products and
     * works. No prices, no people, no settings, no revenue.
     *
     * @return array<int, string>
     */
    public static function waiter(): array
    {
        return [
            'view_any_order',
            'view_order',
            'view_order_receipt',

            'view_any_product',
            'view_product',
            ...self::images('product'),

            'view_any_work',
            'view_work',
            ...self::images('work'),
        ];
    }

    /** @return array<int, string> */
    public static function crud(string $subject): array
    {
        return [
            "view_any_{$subject}",
            "view_{$subject}",
            "create_{$subject}",
            "update_{$subject}",
            "delete_{$subject}",
        ];
    }

    /** @return array<int, string> */
    public static function images(string $subject): array
    {
        return [
            "create_{$subject}_image",
            "update_{$subject}_image",
            "delete_{$subject}_image",
        ];
    }
}
