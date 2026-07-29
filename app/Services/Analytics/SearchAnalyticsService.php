<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Contracts\Search\SearchAnalyticsServiceInterface;
use App\Models\SearchLog;
use App\Models\SearchSuggestionClick;

final class SearchAnalyticsService implements SearchAnalyticsServiceInterface
{
    public function logSearch(string $query, int $resultsCount, ?int $userId = null, array $filters = []): void
    {
        $normalized = $this->normalize($query);

        if ($normalized === '') {
            return;
        }

        SearchLog::query()->create([
            'query' => $normalized,
            'results_count' => $resultsCount,
            'user_id' => $userId,
            'filters' => $filters,
            'created_at' => now(),
        ]);
    }

    public function logSuggestionClick(string $query, int $productId, ?int $userId = null): void
    {
        SearchSuggestionClick::query()->create([
            'query' => $this->normalize($query),
            'product_id' => $productId,
            'user_id' => $userId,
            'created_at' => now(),
        ]);
    }

    public function getTopQueries(int $limit = 20, int $days = 7): array
    {
        return SearchLog::query()
            ->selectRaw('query, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('query')
            ->orderByDesc('count')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => ['query' => $row->query, 'count' => (int) $row->count])
            ->all();
    }

    public function getZeroResultQueries(int $limit = 20, int $days = 7): array
    {
        return SearchLog::query()
            ->selectRaw('query, COUNT(*) as count')
            ->where('results_count', 0)
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('query')
            ->orderByDesc('count')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => ['query' => $row->query, 'count' => (int) $row->count])
            ->all();
    }

    private function normalize(string $query): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', $query) ?? ''));
    }
}
