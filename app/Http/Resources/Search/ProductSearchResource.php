<?php

declare(strict_types=1);

namespace App\Http\Resources\Search;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Formats a raw Elasticsearch product source array (with merged '_highlight')
 * into a stable API shape. Deliberately does NOT wrap an Eloquent model —
 * search results are read directly from the ES index for performance.
 */
final class ProductSearchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $highlight = $this->resource['_highlight'] ?? [];
        $price = (float) $this->resource['price'];
        $salePrice = isset($this->resource['sale_price']) ? (float) $this->resource['sale_price'] : null;

        return [
            'id' => $this->resource['id'],
            'name' => $this->resource['name'],
            'slug' => $this->resource['slug'],
            'sku' => $this->resource['sku'],
            'description' => $this->resource['description'] ?? null,
            'image_url' => $this->resource['image_url'] ?? null,
            'price' => $price,
            'sale_price' => $salePrice,
            'discount_percent' => $salePrice !== null && $price > 0
                ? (int) round((1 - $salePrice / $price) * 100)
                : null,
            'rating' => (float) ($this->resource['rating'] ?? 0),
            'reviews_count' => (int) ($this->resource['reviews_count'] ?? 0),
            'in_stock' => (bool) ($this->resource['in_stock'] ?? false),
            'stock' => (int) ($this->resource['stock'] ?? 0),
            'brand' => $this->resource['brand'] ?? null,
            'category' => $this->resource['category'] ?? null,
            'highlight' => [
                'name' => $highlight['name'][0] ?? null,
                'description' => $highlight['description'][0] ?? null,
            ],
        ];
    }
}