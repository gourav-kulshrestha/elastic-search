<?php

declare(strict_types=1);

namespace App\Services\Search;

use App\Contracts\Search\IndexManagerInterface;
use App\Exceptions\Search\SearchException;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

final class IndexManager implements IndexManagerInterface
{
    private readonly string $alias;

    /** @var array<string, mixed> */
    private readonly array $settings;

    public function __construct(private readonly Client $client)
    {
        $this->alias = config('elasticsearch.indices.products.alias');
        $this->settings = config('elasticsearch.indices.products.settings');
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function createIndex(): string
    {
        $indexName = $this->generateIndexName();

        try {
            $this->client->indices()->create([
                'index' => $indexName,
                'body' => [
                    'settings' => $this->settings,
                    'mappings' => $this->getMapping(),
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Elasticsearch index creation failed', [
                'index' => $indexName,
                'error' => $e->getMessage(),
            ]);

            throw SearchException::indexCreationFailed($e);
        }

        return $indexName;
    }

    public function aliasExists(): bool
    {
        try {
            $response = $this->client->indices()->existsAlias(['name' => $this->alias]);

            return $response->getStatusCode() === 200;
        } catch (ClientResponseException|ServerResponseException) {
            return false;
        }
    }

    public function switchAlias(string $newIndex): void
    {
        $actions = [];

        if ($this->aliasExists()) {
            foreach ($this->resolveIndices() as $oldIndex) {
                $actions[] = ['remove' => ['index' => $oldIndex, 'alias' => $this->alias]];
            }
        }

        $actions[] = ['add' => ['index' => $newIndex, 'alias' => $this->alias]];

        $this->client->indices()->updateAliases(['body' => ['actions' => $actions]]);
    }

    public function resolveIndices(): array
    {
        try {
            $response = $this->client->indices()->getAlias(['name' => $this->alias]);

            return array_keys($response->asArray());
        } catch (ClientResponseException|ServerResponseException) {
            return [];
        }
    }

    public function deleteIndex(string $index): void
    {
        try {
            $this->client->indices()->delete(['index' => $index]);
        } catch (ClientResponseException|ServerResponseException) {
            // Index already absent — safe to ignore.
        }
    }

    public function getMapping(): array
    {
        return [
            'properties' => [
                'id' => ['type' => 'integer'],
                'name' => [
                    'type' => 'text',
                    'analyzer' => 'text_analyzer',
                    'fields' => [
                        'raw' => ['type' => 'keyword'],
                        'autocomplete' => [
                            'type' => 'text',
                            'analyzer' => 'edge_ngram_analyzer',
                            'search_analyzer' => 'search_analyzer',
                        ],
                    ],
                ],
                'description' => [
                    'type' => 'text',
                    'analyzer' => 'text_analyzer',
                ],
                'sku' => ['type' => 'keyword'],
                'slug' => ['type' => 'keyword'],
                'image_url' => ['type' => 'keyword', 'index' => false],
                'price' => ['type' => 'scaled_float', 'scaling_factor' => 100],
                'sale_price' => ['type' => 'scaled_float', 'scaling_factor' => 100],
                'rating' => ['type' => 'half_float'],
                'reviews_count' => ['type' => 'integer'],
                'stock' => ['type' => 'integer'],
                'in_stock' => ['type' => 'boolean'],
                'popularity' => ['type' => 'integer'],
                'brand' => [
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'name' => ['type' => 'keyword'],
                    ],
                ],
                'category' => [
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'name' => ['type' => 'keyword'],
                        'path' => ['type' => 'keyword'],
                    ],
                ],
                'suggest' => [
                    'type' => 'completion',
                    'contexts' => [
                        ['name' => 'category', 'type' => 'category'],
                    ],
                ],
                'created_at' => ['type' => 'date'],
                'updated_at' => ['type' => 'date'],
            ],
        ];
    }

    private function generateIndexName(): string
    {
        return sprintf('%s_v%s', $this->alias, Str::of(now()->format('YmdHisu'))->limit(20, ''));
    }
}