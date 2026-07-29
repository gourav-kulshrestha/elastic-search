<?php

declare(strict_types=1);

namespace App\Contracts\Search;

use App\Models\Product;
use Illuminate\Support\Collection;

interface ProductIndexerInterface
{
    public function index(Product $product): void;

    public function delete(int $productId): void;

    /**
     * @param Collection<int, Product> $products
     */
    public function bulkIndex(Collection $products): void;

    /**
     * Reindex the entire products table into a fresh index and atomically
     * switch the alias, then remove the old index.
     */
    public function reindexAll(): void;
}