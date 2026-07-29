<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Contracts\Search\IndexManagerInterface;
use Illuminate\Console\Command;

final class DeleteProductsIndexCommand extends Command
{
    protected $signature = 'products:index-delete {--force : Skip confirmation prompt}';

    protected $description = 'Delete all physical indices currently behind the products alias.';

    public function handle(IndexManagerInterface $indexManager): int
    {
        $indices = $indexManager->resolveIndices();

        if (empty($indices)) {
            $this->info("No indices found behind alias '{$indexManager->getAlias()}'.");

            return self::SUCCESS;
        }

        $this->warn('This will delete: ' . implode(', ', $indices));

        if (! $this->option('force') && ! $this->confirm('Are you sure you want to continue?')) {
            $this->line('Aborted.');

            return self::SUCCESS;
        }

        foreach ($indices as $index) {
            $indexManager->deleteIndex($index);
            $this->info("Deleted: {$index}");
        }

        return self::SUCCESS;
    }
}