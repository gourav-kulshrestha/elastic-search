<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Contracts\Search\IndexManagerInterface;
use App\Services\Search\SuggestService;
use Elastic\Elasticsearch\ClientBuilder;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Psr\Http\Client\ClientInterface as PsrClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tests\TestCase;

final class SuggestServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_suggest_returns_products_from_search_hits(): void
    {
        config([
            'cache.default' => 'array',
            'elasticsearch.cache.prefix' => 'test-suggest',
        ]);

        Cache::flush();

        $responsePayload = [
            'hits' => [
                'hits' => [
                    [
                        '_source' => [
                            'id' => 10,
                            'name' => 'Smartphone Case',
                            'slug' => 'smartphone-case',
                            'image_url' => 'https://example.test/case.jpg',
                            'price' => 49.99,
                            'sale_price' => 39.99,
                        ],
                    ],
                    [
                        '_source' => [
                            'id' => 11,
                            'name' => 'Phone Stand',
                            'slug' => 'phone-stand',
                            'image_url' => null,
                            'price' => 19.99,
                            'sale_price' => null,
                        ],
                    ],
                ],
            ],
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

        $suggestions = (new SuggestService($client, $indexManager))->suggest('phone', 8);

        $this->assertSame('phone', $httpClient->bodies[0]['query']['bool']['should'][0]['multi_match']['query']);
        $this->assertSame('Smartphone Case', $suggestions[0]['text']);
        $this->assertSame('smartphone-case', $suggestions[0]['slug']);
        $this->assertSame(39.99, $suggestions[0]['price']);
        $this->assertSame('Phone Stand', $suggestions[1]['text']);
        $this->assertSame(19.99, $suggestions[1]['price']);
    }
}
