<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\View\View;

final class ProductPageController extends Controller
{
    public function __invoke(string $slug): View
    {
        $product = Product::query()
            ->with(['brand', 'category'])
            ->where('slug', $slug)
            ->firstOrFail();

        $relatedProducts = Product::query()
            ->with(['brand', 'category'])
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)
            ->orderByDesc('popularity')
            ->limit(4)
            ->get();

        return view('products.show', [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
        ]);
    }
}
