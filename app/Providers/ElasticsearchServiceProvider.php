<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Search\IndexManagerInterface;
use App\Contracts\Search\ProductIndexerInterface;
use App\Contracts\Search\ProductSearchServiceInterface;
use App\Contracts\Search\SearchAnalyticsServiceInterface;
use App\Contracts\Search\SuggestServiceInterface;
use App\Services\Analytics\SearchAnalyticsService;
use App\Services\Search\IndexManager;
use App\Services\Search\ProductIndexer;
use App\Services\Search\ProductSearchService;
use App\Services\Search\SuggestService;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Illuminate\Support\ServiceProvider;

class ElasticsearchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Client::class, function () {
            $builder = ClientBuilder::create()
                ->setHosts(config('elasticsearch.hosts'))
                ->setRetries(config('elasticsearch.retries'));

            if ($apiKey = config('elasticsearch.api_key')) {
                $builder->setApiKey($apiKey);
            } elseif (config('elasticsearch.username') && config('elasticsearch.password')) {
                $builder->setBasicAuthentication(
                    config('elasticsearch.username'),
                    config('elasticsearch.password')
                );
            }

            if (config('elasticsearch.ssl_verification') === false) {
                $builder->setSSLVerification(false);
            }

            return $builder->build();
        });

        $this->app->singleton(IndexManagerInterface::class, IndexManager::class);
        $this->app->singleton(ProductIndexerInterface::class, ProductIndexer::class);
        $this->app->singleton(ProductSearchServiceInterface::class, ProductSearchService::class);
        $this->app->singleton(SuggestServiceInterface::class, SuggestService::class);
        $this->app->singleton(SearchAnalyticsServiceInterface::class, SearchAnalyticsService::class);
    }
}