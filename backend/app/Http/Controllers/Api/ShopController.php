<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;

class ShopController extends Controller
{
    /** Catalogue for the shop screen: categories plus every visible product. */
    public function index(): JsonResponse
    {
        return response()->json([
            'deliveryFee' => (int) SiteSetting::current()->delivery_fee_kobo,
            'categories' => Category::query()
                ->active()
                ->ordered()
                ->get()
                ->map(fn (Category $category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                ])
                ->values(),
            'products' => Product::query()
                ->active()
                ->ordered()
                ->with('category')
                ->get()
                ->map(fn (Product $product) => $this->present($product))
                ->values(),
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $product = Product::query()
            ->active()
            ->with('category')
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json(['product' => $this->present($product)]);
    }

    /** @return array<string, mixed> */
    protected function present(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'description' => $product->description,
            'priceKobo' => $product->price_kobo,
            'unitLabel' => $product->unit_label,
            'image' => $product->imageSrc(),
            'stock' => $product->stock,
            'inStock' => $product->isInStock(),
            'category' => $product->category?->slug,
            'categoryName' => $product->category?->name,
        ];
    }
}
