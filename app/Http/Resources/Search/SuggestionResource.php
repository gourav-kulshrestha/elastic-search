<?php

declare(strict_types=1);

namespace App\Http\Resources\Search;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SuggestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'text' => $this->resource['text'],
            'type' => $this->resource['type'],
            'id' => $this->resource['id'],
            'slug' => $this->resource['slug'] ?? null,
            'image' => $this->resource['image'] ?? null,
            'price' => $this->resource['price'] ?? null,
        ];
    }
}