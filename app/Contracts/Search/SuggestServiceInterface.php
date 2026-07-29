<?php

declare(strict_types=1);

namespace App\Contracts\Search;

interface SuggestServiceInterface
{
    /**
     * @return array<int, array{text: string, type: string, id: ?int, image: ?string}>
     */
    public function suggest(string $query, int $limit = 8): array;
}