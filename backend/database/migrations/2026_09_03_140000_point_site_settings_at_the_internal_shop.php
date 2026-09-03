<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Installs seeded before the shop existed still hold the scraped external shop
 * URL, because model defaults only apply when the row is first created. This
 * rewrites those leftovers — and only those, so a deliberate custom URL stays.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('site_settings')
            ->where('shop_url', 'like', '%shop.travisscott.com%')
            ->update(['shop_url' => '/shop']);

        DB::table('site_settings')
            ->where('terms_url', 'like', '%shop.travisscott.com%')
            ->update(['terms_url' => '/terms']);

        DB::table('site_settings')
            ->whereNotNull('menu_items')
            ->get(['id', 'menu_items'])
            ->each(function (object $row): void {
                $items = json_decode((string) $row->menu_items, true);

                if (! is_array($items)) {
                    return;
                }

                $changed = false;

                foreach ($items as $index => $item) {
                    if (! is_array($item) || ! str_contains((string) ($item['url'] ?? ''), 'shop.travisscott.com')) {
                        continue;
                    }

                    $items[$index]['url'] = '/shop';
                    $items[$index]['external'] = false;
                    $changed = true;
                }

                if ($changed) {
                    DB::table('site_settings')
                        ->where('id', $row->id)
                        ->update(['menu_items' => json_encode($items)]);
                }
            });
    }

    public function down(): void
    {
        // Not reversible: the previous value was a third-party store URL.
    }
};
