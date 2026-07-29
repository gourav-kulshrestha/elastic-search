<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\Search\ProductIndexerInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

final class DeleteProductJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 60];

    public function __construct(private readonly int $productId)
    {
        $this->onQueue('search-indexing');
    }

    public function handle(ProductIndexerInterface $indexer): void
    {
        $indexer->delete($this->productId);
    }

    public function failed(Throwable $exception): void
    {
        report($exception);
    }
}