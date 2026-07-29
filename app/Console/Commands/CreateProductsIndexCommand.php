<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Contracts\Search\IndexManagerInterface;
use Illuminate\Console\Command;
use Throwable;

final class CreateProductsIndexCommand extends Command
{
    protected $signature = 'products:index-create
                            {--switch : Switch the alias to the newly created index}
                            {--fresh : Delete existing indices behind the alias before creating a new one}';

    protected $description = 'Create a new physical Elasticsearch index for products (mapping + settings only, no data).';

    public function handle(IndexManagerInterface $indexManager): int
    {
        $this->info("Creating index for alias '{$indexManager->getAlias()}'...");

        try {
            $newIndex = $indexManager->createIndex();
        } catch (Throwable $e) {
            $this->error('Index creation failed: ' . $e->getMessage());

            if ($e->getPrevious() !== null) {
                $this->error('Caused by: ' . $e->getPrevious()->getMessage());
            }

            return self::FAILURE;
        }

        $this->info("Created index: {$newIndex}");

        if ($this->option('switch') || $this->option('fresh')) {
            $oldIndices = $indexManager->resolveIndices();

            $indexManager->switchAlias($newIndex);
            $this->info("Alias '{$indexManager->getAlias()}' now points to {$newIndex}.");

            if ($this->option('fresh')) {
                foreach ($oldIndices as $oldIndex) {
                    $indexManager->deleteIndex($oldIndex);
                    $this->info("Deleted old index: {$oldIndex}");
                }
            }
        } else {
            $this->line("Alias not switched. Run again with --switch to activate this index, or use 'products:reindex' to create, populate, and switch in one step.");
        }

        return self::SUCCESS;
    }
}