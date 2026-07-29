<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Contracts\Search\ProductIndexerInterface;
use Illuminate\Console\Command;
use Throwable;

final class ReindexProductsCommand extends Command
{
    protected $signature = 'products:reindex';

    protected $description = 'Rebuild the products Elasticsearch index from the database.';

    public function handle(ProductIndexerInterface $indexer): int
    {
        $this->info('Starting full product reindex...');
        $start = microtime(true);

        try {
            $indexer->reindexAll();
        } catch (Throwable $e) {
            $this->error('Reindex failed: ' . $e->getMessage());

            if ($e->getPrevious() !== null) {
                $this->error('Caused by: ' . $e->getPrevious()->getMessage());
            }

            return self::FAILURE;
        }

        $elapsed = round(microtime(true) - $start, 2);
        $this->info("Reindex completed successfully in {$elapsed}s.");

        return self::SUCCESS;
    }
}