<?php

declare(strict_types=1);

namespace App\Exceptions\Search;

use Exception;
use Throwable;

class SearchException extends Exception
{
    public static function queryFailed(Throwable $previous): self
    {
        return new self('Failed to execute search query.', 0, $previous);
    }

    public static function indexingFailed(int $productId, Throwable $previous): self
    {
        return new self("Failed to index product #{$productId}.", 0, $previous);
    }

    public static function suggestionFailed(Throwable $previous): self
    {
        return new self('Failed to fetch search suggestions.', 0, $previous);
    }

    public static function indexCreationFailed(Throwable $previous): self
    {
        return new self('Failed to create Elasticsearch index.', 0, $previous);
    }
}