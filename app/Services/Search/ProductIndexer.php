<?php

declare(strict_types=1);

namespace App\Services\Search;

use App\Contracts\Search\IndexManagerInterface;
use App\Contracts\Search\ProductIndexerInterface;
use App\Exceptions\Search\SearchException;
use App\Models\Product;
use Elastic\Elasticsearch\Client;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ProductIndexer implements ProductIndexerInterface
{
    public function __construct(
        private readonly Client $client,
        private readonly IndexManagerInterface $indexManager,
    ) {
    }

    public function index(Product $product): void
    {
        try {
            $this->client->index([
                'index' => $this->indexManager->getAlias(),
                'id' => (string) $product->id,
                'body' => $this->toDocument($product),
            ]);
        } catch (Throwable $e) {
            Log::error('Elasticsearch product index failed', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
            ]);

            throw SearchException::indexingFailed($product->id, $e);
        }
    }

    public function delete(int $productId): void
    {
        try {
            $this->client->delete([
                'index' => $this->indexManager->getAlias(),
                'id' => (string) $productId,
            ]);
        } catch (Throwable $e) {
            if (! str_contains($e->getMessage(), '404')) {
                Log::error('Elasticsearch product delete failed', [
                    'product_id' => $productId,
                    'error' => $e->getMessage(),
                ]);

                throw SearchException::indexingFailed($productId, $e);
            }
        }
    }

    public function bulkIndex(Collection $products): void
    {
        if ($products->isEmpty()) {
            return;
        }

        $alias = $this->indexManager->getAlias();
        $body = [];

        foreach ($products as $product) {
            $body[] = ['index' => ['_index' => $alias, '_id' => (string) $product->id]];
            $body[] = $this->toDocument($product);
        }

        $response = $this->client->bulk(['body' => $body])->asArray();

        if ($response['errors'] ?? false) {
            $failed = collect($response['items'])
                ->filter(fn (array $item) => isset($item['index']['error']))
                ->map(fn (array $item) => $item['index']['_id'] . ': ' . $item['index']['error']['reason']);

            Log::error('Elasticsearch bulk index encountered errors', ['failures' => $failed->all()]);
        }
    }

    public function reindexAll(): void
    {
        $newIndex = $this->indexManager->createIndex();
        $oldIndices = $this->indexManager->resolveIndices();

        try {
            $chunkSize = (int) config('elasticsearch.bulk_chunk_size', 500);

            Product::query()
                ->with(['brand', 'category'])
                ->orderBy('id')
                ->chunkById($chunkSize, function (Collection $products) use ($newIndex) {
                    $body = [];

                    foreach ($products as $product) {
                        $body[] = ['index' => ['_index' => $newIndex, '_id' => (string) $product->id]];
                        $body[] = $this->toDocument($product);
                    }

                    $this->client->bulk(['body' => $body]);
                });

            $this->indexManager->switchAlias($newIndex);

            foreach ($oldIndices as $oldIndex) {
                $this->indexManager->deleteIndex($oldIndex);
            }
        } catch (Throwable $e) {
            $this->indexManager->deleteIndex($newIndex);

            throw SearchException::indexCreationFailed($e);
        }
    }

    private function toDocument(Product $product): array
    {
        $effectivePrice = $product->sale_price ?? $product->price;

        return [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'sku' => $product->sku,
            'slug' => $product->slug,
            'image_url' => $product->image_url,
            'price' => (float) $product->price,
            'sale_price' => $product->sale_price !== null ? (float) $product->sale_price : null,
            'rating' => (float) $product->rating,
            'reviews_count' => (int) $product->reviews_count,
            'stock' => (int) $product->stock,
            'in_stock' => $product->stock > 0,
            'popularity' => (int) $product->popularity,
            'brand' => $product->brand ? [
                'id' => $product->brand->id,
                'name' => $product->brand->name,
            ] : null,
            'category' => $product->category ? [
                'id' => $product->category->id,
                'name' => $product->category->name,
                'path' => $product->category->path ?? $product->category->name,
            ] : null,
            'suggest' => [
                'input' => array_values(array_filter([
                    $product->name,
                    $product->brand?->name,
                    $product->sku,
                ])),
                'weight' => min(100, max(1, (int) $product->popularity)),
                'contexts' => [
                    'category' => $product->category ? [(string) $product->category->id] : [],
                ],
            ],
            'created_at' => $product->created_at?->toIso8601String(),
            'updated_at' => $product->updated_at?->toIso8601String(),
        ];
    }
}