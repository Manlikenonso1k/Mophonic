<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Models\Subscriber;
use App\Models\Work;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    /** Everything the front end needs to render the Mophonik page. */
    public function index(): JsonResponse
    {
        $settings = SiteSetting::current();

        return response()->json([
            'settings' => [
                'eyebrow' => $settings->eyebrow,
                'heading' => $settings->heading,
                'shopUrl' => $settings->shop_url,
                'termsUrl' => $settings->terms_url,
                'newsletterHeading' => $settings->newsletter_heading,
                'menuItems' => $settings->menu_items ?? SiteSetting::defaultMenuItems(),
            ],
            'works' => Work::query()
                ->active()
                ->ordered()
                ->get()
                ->map(fn (Work $work) => [
                    'id' => $work->id,
                    'title' => $work->title,
                    'slug' => $work->slug,
                    'type' => $work->type,
                    'description' => $work->description,
                    'cover' => $work->coverSrc(),
                    'video' => $work->backgroundVideoSrc(),
                    'href' => $work->link_url ?: "/mophonik/{$work->slug}",
                ])
                ->values(),
        ]);
    }

    public function subscribe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
            'terms' => ['accepted'],
        ]);

        Subscriber::query()->firstOrCreate(
            ['email' => strtolower($data['email'])],
            ['source' => 'mophonik'],
        );

        return response()->json(['message' => 'Subscribed']);
    }
}
