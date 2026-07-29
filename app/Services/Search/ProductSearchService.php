<?php

declare(strict_types=1);

namespace App\Services\Search;

use App\Contracts\Search\IndexManagerInterface;
use App\Contracts\Search\ProductSearchServiceInterface;
use App\DTOs\Search\SearchFiltersDTO;
use App\DTOs\Search\SearchResultDTO;
use App\Exceptions\Search\SearchException;
use Elastic\Elasticsearch\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ProductSearchService implements ProductSearchServiceInterface
{
    private const SORT_MAP = [
        'relevance' => null, // handled by _score
        'price' => 'price',
        'rating' => 'rating',
        'newest' => 'created_at',
        'popularity' => 'popularity',
    ];

    public function __construct(
        private readonly Client $client,
        private readonly IndexManagerInterface $indexManager,
    ) {}

    public function search(SearchFiltersDTO $filters): SearchResultDTO
    {
        $cacheKey = $this->cacheKey($filters);
        $ttl = (int) config('elasticsearch.cache.ttl', 300);
        $cached = Cache::get($cacheKey);

        if (is_array($cached) && $this->isCachedResultPayload($cached)) {
            return $this->fromCachedPayload($cached);
        }

        if ($cached !== null) {
            Cache::forget($cacheKey);
        }

        try {
            $response = $this->client->search([
                'index' => $this->indexManager->getAlias(),
                'body' => $this->buildQuery($filters),
            ])->asArray();
        } catch (Throwable $e) {
            Log::error('Elasticsearch search query failed', [
                'error' => $e->getMessage(),
                'filters' => get_object_vars($filters),
            ]);

            throw SearchException::queryFailed($e);
        }

        $result = $this->toResultDTO($response, $filters);

        Cache::put($cacheKey, $this->toCachedPayload($result), $ttl);

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildQuery(SearchFiltersDTO $filters): array
    {
        $filter = $this->buildFilters($filters);

        $query = [
            'track_total_hits' => true,
            'from' => $filters->offset(),
            'size' => $filters->perPage,
            'query' => $this->buildSearchQuery($filters->query),
            'sort' => $this->buildSort($filters),
            'highlight' => [
                'pre_tags' => ['<mark>'],
                'post_tags' => ['</mark>'],
                'fields' => [
                    'name' => ['number_of_fragments' => 0],
                    'description' => ['fragment_size' => 150, 'number_of_fragments' => 1],
                ],
            ],
            'aggs' => $this->buildAggregations($filter),
        ];

        if ($filter !== []) {
            $query['post_filter'] = $this->filterQuery($filter);
        }

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSearchQuery(?string $query): array
    {
        $query = trim((string) $query);

        if ($query === '') {
            return ['match_all' => new \stdClass];
        }

        $wildcard = $this->wildcardValue($query);

        return [
            'bool' => [
                'should' => [
                    [
                        'multi_match' => [
                            'query' => $query,
                            'fields' => ['name^4', 'description'],
                            'type' => 'best_fields',
                            'fuzziness' => 'AUTO',
                            'operator' => 'and',
                        ],
                    ],
                    [
                        'multi_match' => [
                            'query' => $query,
                            'fields' => ['name.autocomplete^4', 'name^2'],
                            'type' => 'bool_prefix',
                        ],
                    ],
                    ['wildcard' => ['brand.name' => ['value' => $wildcard, 'case_insensitive' => true, 'boost' => 2]]],
                    ['wildcard' => ['category.name' => ['value' => $wildcard, 'case_insensitive' => true, 'boost' => 2]]],
                    ['wildcard' => ['category.path' => ['value' => $wildcard, 'case_insensitive' => true, 'boost' => 1.5]]],
                    ['wildcard' => ['sku' => ['value' => $wildcard, 'case_insensitive' => true]]],
                ],
                'minimum_should_match' => 1,
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function buildFilters(SearchFiltersDTO $filters): array
    {
        $filter = [];

        if (! empty($filters->brandIds)) {
            $filter['brand'] = ['terms' => ['brand.id' => $filters->brandIds]];
        }

        if (! empty($filters->categoryIds)) {
            $filter['category'] = ['terms' => ['category.id' => $filters->categoryIds]];
        }

        if ($filters->minPrice !== null || $filters->maxPrice !== null) {
            $range = [];
            if ($filters->minPrice !== null) {
                $range['gte'] = $filters->minPrice;
            }
            if ($filters->maxPrice !== null) {
                $range['lte'] = $filters->maxPrice;
            }
            $filter['price'] = ['range' => ['price' => $range]];
        }

        if ($filters->minRating !== null) {
            $filter['rating'] = ['range' => ['rating' => ['gte' => $filters->minRating]]];
        }

        if ($filters->inStockOnly) {
            $filter['stock'] = ['term' => ['in_stock' => true]];
        }

        return $filter;
    }

    /**
     * @return array<int, array<string, mixed>|string>
     */
    private function buildSort(SearchFiltersDTO $filters): array
    {
        $field = self::SORT_MAP[$filters->sortBy] ?? null;
        $direction = in_array($filters->sortDirection, ['asc', 'desc'], true) ? $filters->sortDirection : 'desc';

        if ($field === null) {
            // Relevance: text score first, then a stable popularity tiebreaker.
            return ['_score', ['popularity' => 'desc']];
        }

        return [[$field => $direction], '_score'];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildAggregations(array $filters): array
    {
        return [
            'brands' => $this->facetAggregation($filters, ['brand'], [
                'terms' => ['field' => 'brand.id', 'size' => 30],
                'aggs' => ['name' => ['terms' => ['field' => 'brand.name', 'size' => 1]]],
            ]),
            'categories' => $this->facetAggregation($filters, ['category'], [
                'terms' => ['field' => 'category.id', 'size' => 30],
                'aggs' => ['name' => ['terms' => ['field' => 'category.name', 'size' => 1]]],
            ]),
            'rating_ranges' => $this->facetAggregation($filters, ['rating'], [
                'range' => [
                    'field' => 'rating',
                    'ranges' => [
                        ['key' => '4_plus', 'from' => 4],
                        ['key' => '3_plus', 'from' => 3],
                        ['key' => '2_plus', 'from' => 2],
                        ['key' => '1_plus', 'from' => 1],
                    ],
                ],
            ]),
            'price_stats' => [
                'filter' => $this->filterQuery($filters),
                'aggs' => ['values' => ['stats' => ['field' => 'price']]],
            ],
            'in_stock_count' => [
                'filter' => $this->filterQuery([
                    ...array_diff_key($filters, ['stock' => true]),
                    'stock' => ['term' => ['in_stock' => true]],
                ]),
            ],
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $filters
     * @param  array<int, string>  $except
     * @param  array<string, mixed>  $aggregation
     * @return array<string, mixed>
     */
    private function facetAggregation(array $filters, array $except, array $aggregation): array
    {
        return [
            'filter' => $this->filterQuery(array_diff_key($filters, array_flip($except))),
            'aggs' => ['values' => $aggregation],
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $filters
     * @return array<string, mixed>
     */
    private function filterQuery(array $filters): array
    {
        if ($filters === []) {
            return ['match_all' => new \stdClass];
        }

        return ['bool' => ['filter' => array_values($filters)]];
    }

    private function wildcardValue(string $query): string
    {
        return '*'.addcslashes($query, '\\*?').'*';
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function toResultDTO(array $response, SearchFiltersDTO $filters): SearchResultDTO
    {
        $hits = array_map(function (array $hit) {
            $source = $hit['_source'];
            $source['_highlight'] = $hit['highlight'] ?? [];

            return $source;
        }, $response['hits']['hits']);

        return new SearchResultDTO(
            hits: $hits,
            total: (int) $response['hits']['total']['value'],
            page: $filters->page,
            perPage: $filters->perPage,
            aggregations: $this->normalizeAggregations($response['aggregations'] ?? []),
            tookMs: (int) ($response['took'] ?? 0),
        );
    }

    /**
     * @param  array<string, mixed>  $aggs
     * @return array<string, mixed>
     */
    private function normalizeAggregations(array $aggs): array
    {
        $mapBuckets = fn (array $buckets, string $nameAgg = 'name') => array_map(
            fn (array $bucket) => [
                'id' => $bucket['key'],
                'label' => $bucket[$nameAgg]['buckets'][0]['key'] ?? (string) $bucket['key'],
                'count' => $bucket['doc_count'],
            ],
            $buckets
        );

        return [
            'brands' => $mapBuckets($aggs['brands']['values']['buckets'] ?? $aggs['brands']['buckets'] ?? []),
            'categories' => $mapBuckets($aggs['categories']['values']['buckets'] ?? $aggs['categories']['buckets'] ?? []),
            'rating_ranges' => array_map(
                fn (array $b) => ['key' => $b['key'], 'count' => $b['doc_count']],
                $aggs['rating_ranges']['values']['buckets'] ?? $aggs['rating_ranges']['buckets'] ?? []
            ),
            'price' => [
                'min' => $aggs['price_stats']['values']['min'] ?? $aggs['price_stats']['min'] ?? null,
                'max' => $aggs['price_stats']['values']['max'] ?? $aggs['price_stats']['max'] ?? null,
                'avg' => $aggs['price_stats']['values']['avg'] ?? $aggs['price_stats']['avg'] ?? null,
            ],
            'in_stock_count' => $aggs['in_stock_count']['doc_count'] ?? 0,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function isCachedResultPayload(array $payload): bool
    {
        return ($payload['_type'] ?? null) === 'search_result_v2'
            && is_array($payload['hits'] ?? null)
            && is_int($payload['total'] ?? null)
            && is_int($payload['page'] ?? null)
            && is_int($payload['per_page'] ?? null)
            && is_array($payload['aggregations'] ?? null)
            && is_int($payload['took_ms'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function fromCachedPayload(array $payload): SearchResultDTO
    {
        return new SearchResultDTO(
            hits: $payload['hits'],
            total: $payload['total'],
            page: $payload['page'],
            perPage: $payload['per_page'],
            aggregations: $payload['aggregations'],
            tookMs: $payload['took_ms'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function toCachedPayload(SearchResultDTO $result): array
    {
        return [
            '_type' => 'search_result_v2',
            'hits' => $result->hits,
            'total' => $result->total,
            'page' => $result->page,
            'per_page' => $result->perPage,
            'aggregations' => $result->aggregations,
            'took_ms' => $result->tookMs,
        ];
    }

    private function cacheKey(SearchFiltersDTO $filters): string
    {
        return sprintf('%s:products:%s', config('elasticsearch.cache.prefix'), $filters->cacheKey());
    }
}
