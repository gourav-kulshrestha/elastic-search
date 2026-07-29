<?php

declare(strict_types=1);

namespace App\Services\Search;

use App\Contracts\Search\IndexManagerInterface;
use App\Contracts\Search\SuggestServiceInterface;
use App\Exceptions\Search\SearchException;
use Elastic\Elasticsearch\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

final class SuggestService implements SuggestServiceInterface
{
    public function __construct(
        private readonly Client $client,
        private readonly IndexManagerInterface $indexManager,
    ) {}

    public function suggest(string $query, int $limit = 8): array
    {
        $query = trim($query);

        if ($query === '') {
            return [];
        }

        $cacheKey = sprintf(
            '%s:suggest:v2:%s:%d',
            config('elasticsearch.cache.prefix'),
            md5(mb_strtolower($query)),
            $limit
        );

        return Cache::remember($cacheKey, 60, function () use ($query, $limit) {
            try {
                $response = $this->client->search([
                    'index' => $this->indexManager->getAlias(),
                    'body' => $this->buildQuery($query, $limit),
                ])->asArray();
            } catch (Throwable $e) {
                Log::error('Elasticsearch suggest query failed', ['error' => $e->getMessage()]);

                throw SearchException::suggestionFailed($e);
            }

            return $this->normalize($response);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function buildQuery(string $query, int $limit): array
    {
        $wildcard = $this->wildcardValue($query);

        return [
            'size' => $limit,
            '_source' => ['id', 'name', 'image_url', 'slug', 'price', 'sale_price'],
            'query' => [
                'bool' => [
                    'should' => [
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
            ],
            'sort' => [
                '_score',
                ['popularity' => 'desc'],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<int, array{text: string, type: string, id: ?int, image: ?string}>
     */
    private function normalize(array $response): array
    {
        $hits = $response['hits']['hits'] ?? [];

        return array_map(function (array $hit) {
            $source = $hit['_source'];

            return [
                'text' => $source['name'],
                'type' => 'product',
                'id' => $source['id'] ?? null,
                'slug' => $source['slug'] ?? null,
                'image' => $source['image_url'] ?? null,
                'price' => $source['sale_price'] ?? $source['price'] ?? null,
            ];
        }, $hits);
    }

    private function wildcardValue(string $query): string
    {
        return '*'.addcslashes($query, '\\*?').'*';
    }
}
