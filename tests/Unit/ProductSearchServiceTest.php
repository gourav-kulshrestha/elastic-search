<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Contracts\Search\IndexManagerInterface;
use App\DTOs\Search\SearchFiltersDTO;
use App\DTOs\Search\SearchResultDTO;
use App\Http\Requests\Search\SearchProductsRequest;
use App\Services\Search\ProductSearchService;
use Elastic\Elasticsearch\ClientBuilder;
use GuzzleHttp\Psr7\Response;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Psr\Http\Client\ClientInterface as PsrClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tests\TestCase;

final class ProductSearchServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_search_rebuilds_results_when_cached_value_is_not_a_dto(): void
    {
        config([
            'cache.default' => 'array',
            'elasticsearch.cache.prefix' => 'test',
            'elasticsearch.cache.ttl' => 60,
        ]);

        Cache::flush();

        $filters = new SearchFiltersDTO(query: 'phone', page: 1, perPage: 10);
        $cacheKey = sprintf('%s:products:%s', config('elasticsearch.cache.prefix'), $filters->cacheKey());
        Cache::put($cacheKey, (object) ['invalid' => true], 60);

        $responsePayload = [
            'hits' => [
                'hits' => [],
                'total' => ['value' => 0],
            ],
            'aggregations' => [],
            'took' => 12,
        ];

        $httpClient = new class($responsePayload) implements PsrClientInterface
        {
            public int $requests = 0;

            public function __construct(private readonly array $payload) {}

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                $this->requests++;

                return new Response(200, [
                    'Content-Type' => 'application/json',
                    'X-Elastic-Product' => 'Elasticsearch',
                ], json_encode($this->payload, JSON_THROW_ON_ERROR));
            }
        };

        $client = ClientBuilder::create()
            ->setHosts(['http://localhost:9200'])
            ->setHttpClient($httpClient)
            ->build();

        $indexManager = Mockery::mock(IndexManagerInterface::class);
        $indexManager->shouldReceive('getAlias')->once()->andReturn('products');

        $service = new ProductSearchService($client, $indexManager);

        $result = $service->search($filters);

        $this->assertInstanceOf(SearchResultDTO::class, $result);
        $this->assertSame(0, $result->total);
        $this->assertSame(10, $result->perPage);
        $this->assertIsArray(Cache::get($cacheKey));
        $this->assertSame(1, $httpClient->requests);
    }

    public function test_search_keeps_brand_and_category_facets_multi_selectable(): void
    {
        config([
            'cache.default' => 'array',
            'elasticsearch.cache.prefix' => 'test-multi-select',
            'elasticsearch.cache.ttl' => 60,
        ]);

        Cache::flush();

        $responsePayload = [
            'hits' => [
                'hits' => [],
                'total' => ['value' => 0],
            ],
            'aggregations' => [],
            'took' => 12,
        ];

        $httpClient = new class($responsePayload) implements PsrClientInterface
        {
            public array $bodies = [];

            public function __construct(private readonly array $payload) {}

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                $this->bodies[] = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);

                return new Response(200, [
                    'Content-Type' => 'application/json',
                    'X-Elastic-Product' => 'Elasticsearch',
                ], json_encode($this->payload, JSON_THROW_ON_ERROR));
            }
        };

        $client = ClientBuilder::create()
            ->setHosts(['http://localhost:9200'])
            ->setHttpClient($httpClient)
            ->build();

        $indexManager = Mockery::mock(IndexManagerInterface::class);
        $indexManager->shouldReceive('getAlias')->once()->andReturn('products');

        $service = new ProductSearchService($client, $indexManager);
        $service->search(new SearchFiltersDTO(
            brandIds: [1, 2],
            categoryIds: [3, 4],
            minRating: 1,
            page: 1,
            perPage: 10,
        ));

        $body = $httpClient->bodies[0];
        $this->assertFiltersContainTerms($body['post_filter']['bool']['filter'], 'brand.id', [1, 2]);
        $this->assertFiltersContainTerms($body['post_filter']['bool']['filter'], 'category.id', [3, 4]);

        $brandFacetFilters = $body['aggs']['brands']['filter']['bool']['filter'];
        $this->assertFiltersDoNotContainTerms($brandFacetFilters, 'brand.id');
        $this->assertFiltersContainTerms($brandFacetFilters, 'category.id', [3, 4]);

        $categoryFacetFilters = $body['aggs']['categories']['filter']['bool']['filter'];
        $this->assertFiltersContainTerms($categoryFacetFilters, 'brand.id', [1, 2]);
        $this->assertFiltersDoNotContainTerms($categoryFacetFilters, 'category.id');
    }

    public function test_search_query_matches_keyword_brand_and_category_names(): void
    {
        config([
            'cache.default' => 'array',
            'elasticsearch.cache.prefix' => 'test-query-keywords',
            'elasticsearch.cache.ttl' => 60,
        ]);

        Cache::flush();

        $responsePayload = [
            'hits' => [
                'hits' => [],
                'total' => ['value' => 0],
            ],
            'aggregations' => [],
            'took' => 12,
        ];

        $httpClient = new class($responsePayload) implements PsrClientInterface
        {
            public array $bodies = [];

            public function __construct(private readonly array $payload) {}

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                $this->bodies[] = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);

                return new Response(200, [
                    'Content-Type' => 'application/json',
                    'X-Elastic-Product' => 'Elasticsearch',
                ], json_encode($this->payload, JSON_THROW_ON_ERROR));
            }
        };

        $client = ClientBuilder::create()
            ->setHosts(['http://localhost:9200'])
            ->setHttpClient($httpClient)
            ->build();

        $indexManager = Mockery::mock(IndexManagerInterface::class);
        $indexManager->shouldReceive('getAlias')->once()->andReturn('products');

        $service = new ProductSearchService($client, $indexManager);
        $service->search(new SearchFiltersDTO(query: 'phone', page: 1, perPage: 10));

        $body = $httpClient->bodies[0];
        $this->assertWildcardQueryExists($body['query']['bool']['should'], 'brand.name', '*phone*');
        $this->assertWildcardQueryExists($body['query']['bool']['should'], 'category.name', '*phone*');
        $this->assertWildcardQueryExists($body['query']['bool']['should'], 'category.path', '*phone*');
    }

    public function test_search_request_accepts_multiple_brand_and_category_parameter_formats(): void
    {
        $request = SearchProductsRequest::create('/api/search/products', 'GET', [
            'brand_ids' => ['1', '2,3'],
            'category_ids' => '4,5',
        ]);
        $request->setContainer($this->app);
        $request->setRedirector($this->app->make(Redirector::class));

        $request->validateResolved();

        $this->assertSame(['1', '2', '3'], $request->validated('brand_ids'));
        $this->assertSame(['4', '5'], $request->validated('category_ids'));
    }

    public function test_search_request_defaults_min_price_when_only_max_price_is_set(): void
    {
        $request = SearchProductsRequest::create('/api/search/products', 'GET', [
            'max_price' => '500',
        ]);
        $request->setContainer($this->app);
        $request->setRedirector($this->app->make(Redirector::class));

        $request->validateResolved();

        $this->assertSame(0, $request->validated('min_price'));
        $this->assertSame('500', $request->validated('max_price'));
    }

    /**
     * @param  array<int, array<string, mixed>>  $filters
     * @param  array<int, int>  $expected
     */
    private function assertFiltersContainTerms(array $filters, string $field, array $expected): void
    {
        foreach ($filters as $filter) {
            if (($filter['terms'][$field] ?? null) === $expected) {
                $this->addToAssertionCount(1);

                return;
            }
        }

        $this->fail(sprintf('Failed asserting that filters contain terms for [%s].', $field));
    }

    /**
     * @param  array<int, array<string, mixed>>  $filters
     */
    private function assertFiltersDoNotContainTerms(array $filters, string $field): void
    {
        foreach ($filters as $filter) {
            if (array_key_exists($field, $filter['terms'] ?? [])) {
                $this->fail(sprintf('Failed asserting that filters do not contain terms for [%s].', $field));
            }
        }

        $this->addToAssertionCount(1);
    }

    /**
     * @param  array<int, array<string, mixed>>  $clauses
     */
    private function assertWildcardQueryExists(array $clauses, string $field, string $value): void
    {
        foreach ($clauses as $clause) {
            if (($clause['wildcard'][$field]['value'] ?? null) === $value) {
                $this->addToAssertionCount(1);

                return;
            }
        }

        $this->fail(sprintf('Failed asserting that query contains wildcard for [%s].', $field));
    }
}
