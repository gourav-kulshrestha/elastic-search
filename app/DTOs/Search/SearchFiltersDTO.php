<?php

declare(strict_types=1);

namespace App\DTOs\Search;

final class SearchFiltersDTO
{
    /**
     * @param array<int, int> $brandIds
     * @param array<int, int> $categoryIds
     */
    public function __construct(
        public readonly ?string $query = null,
        public readonly array $brandIds = [],
        public readonly array $categoryIds = [],
        public readonly ?float $minPrice = null,
        public readonly ?float $maxPrice = null,
        public readonly ?float $minRating = null,
        public readonly bool $inStockOnly = false,
        public readonly string $sortBy = 'relevance',
        public readonly string $sortDirection = 'desc',
        public readonly int $page = 1,
        public readonly int $perPage = 24,
        public readonly ?int $userId = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            query: $data['q'] ?? null,
            brandIds: array_map('intval', $data['brand_ids'] ?? []),
            categoryIds: array_map('intval', $data['category_ids'] ?? []),
            minPrice: isset($data['min_price']) ? (float) $data['min_price'] : null,
            maxPrice: isset($data['max_price']) ? (float) $data['max_price'] : null,
            minRating: isset($data['min_rating']) ? (float) $data['min_rating'] : null,
            inStockOnly: (bool) ($data['in_stock'] ?? false),
            sortBy: $data['sort_by'] ?? 'relevance',
            sortDirection: $data['sort_direction'] ?? 'desc',
            page: max(1, (int) ($data['page'] ?? 1)),
            perPage: min(100, max(1, (int) ($data['per_page'] ?? 24))),
            userId: $data['user_id'] ?? null,
        );
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }

    /**
     * A stable cache key fingerprint for this filter set.
     */
    public function cacheKey(): string
    {
        return md5(json_encode([
            $this->query,
            $this->brandIds,
            $this->categoryIds,
            $this->minPrice,
            $this->maxPrice,
            $this->minRating,
            $this->inStockOnly,
            $this->sortBy,
            $this->sortDirection,
            $this->page,
            $this->perPage,
        ]));
    }
}