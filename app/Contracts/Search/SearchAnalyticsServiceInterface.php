<?php

declare(strict_types=1);

namespace App\Contracts\Search;

interface SearchAnalyticsServiceInterface
{
    public function logSearch(string $query, int $resultsCount, ?int $userId = null, array $filters = []): void;

    public function logSuggestionClick(string $query, int $productId, ?int $userId = null): void;

    /**
     * @return array<int, array{query: string, count: int}>
     */
    public function getTopQueries(int $limit = 20, int $days = 7): array;

    /**
     * @return array<int, array{query: string, count: int}>
     */
    public function getZeroResultQueries(int $limit = 20, int $days = 7): array;
}