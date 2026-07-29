<?php

declare(strict_types=1);

namespace App\Observers;

use App\Jobs\DeleteProductJob;
use App\Jobs\IndexProductJob;
use App\Models\Product;

final class ProductObserver
{
    public function created(Product $product): void
    {
        IndexProductJob::dispatch($product->id)->afterCommit();
    }

    public function updated(Product $product): void
    {
        IndexProductJob::dispatch($product->id)->afterCommit();
    }

    public function deleted(Product $product): void
    {
        DeleteProductJob::dispatch($product->id)->afterCommit();
    }

    public function restored(Product $product): void
    {
        IndexProductJob::dispatch($product->id)->afterCommit();
    }

    public function forceDeleted(Product $product): void
    {
        DeleteProductJob::dispatch($product->id)->afterCommit();
    }
}