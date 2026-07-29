<?php

return [
    'hosts' => explode(',', env('ELASTICSEARCH_HOSTS', 'localhost:9200')),

    'username' => env('ELASTICSEARCH_USERNAME'),
    'password' => env('ELASTICSEARCH_PASSWORD'),
    'api_key' => env('ELASTICSEARCH_API_KEY'),

    'ssl_verification' => env('ELASTICSEARCH_SSL_VERIFICATION', true),

    'retries' => (int) env('ELASTICSEARCH_RETRIES', 2),

    'indices' => [
        'products' => [
            'alias' => env('ELASTICSEARCH_PRODUCTS_ALIAS', 'products'),
            'settings' => [
                'number_of_shards' => (int) env('ELASTICSEARCH_PRODUCTS_SHARDS', 3),
                'number_of_replicas' => (int) env('ELASTICSEARCH_PRODUCTS_REPLICAS', 1),
                'index' => [
                    'max_ngram_diff' => 8,
                ],
                'analysis' => [
                    'filter' => [
                        'edge_ngram_filter' => [
                            'type' => 'edge_ngram',
                            'min_gram' => 2,
                            'max_gram' => 10,
                        ],
                        'english_stemmer' => [
                            'type' => 'stemmer',
                            'language' => 'english',
                        ],
                        'english_stop' => [
                            'type' => 'stop',
                            'stopwords' => '_english_',
                        ],
                    ],
                    'analyzer' => [
                        'edge_ngram_analyzer' => [
                            'type' => 'custom',
                            'tokenizer' => 'standard',
                            'filter' => ['lowercase', 'edge_ngram_filter'],
                        ],
                        'search_analyzer' => [
                            'type' => 'custom',
                            'tokenizer' => 'standard',
                            'filter' => ['lowercase'],
                        ],
                        'text_analyzer' => [
                            'type' => 'custom',
                            'tokenizer' => 'standard',
                            'filter' => ['lowercase', 'english_stop', 'english_stemmer'],
                        ],
                    ],
                ],
            ],
        ],
    ],

    'cache' => [
        'ttl' => (int) env('ELASTICSEARCH_CACHE_TTL', 300),
        'prefix' => env('ELASTICSEARCH_CACHE_PREFIX', 'es_search'),
    ],

    'bulk_chunk_size' => (int) env('ELASTICSEARCH_BULK_CHUNK_SIZE', 500),
];