<?php

declare(strict_types=1);

namespace App\DTOs\Search;

final class SearchResultDTO
{
    /**
     * @param array<int, array<string, mixed>> $hits Raw product source arrays with '_highlight' merged in.
     * @param array<string, mixed> $aggregations Normalized facet aggregations.
     */
    public function __construct(
        public readonly array $hits,
        public readonly int $total,
        public readonly int $page,
        public readonly int $perPage,
        public readonly array $aggregations,
        public readonly int $tookMs,
    ) {
    }

    public function lastPage(): int
    {
        return (int) max(1, ceil($this->total / max(1, $this->perPage)));
    }
}