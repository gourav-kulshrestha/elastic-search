<?php

declare(strict_types=1);

namespace App\Contracts\Search;

use App\DTOs\Search\SearchFiltersDTO;
use App\DTOs\Search\SearchResultDTO;

interface ProductSearchServiceInterface
{
    public function search(SearchFiltersDTO $filters): SearchResultDTO;
}