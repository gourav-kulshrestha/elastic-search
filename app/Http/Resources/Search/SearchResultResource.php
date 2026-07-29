<?php

declare(strict_types=1);

namespace App\Http\Resources\Search;

use App\DTOs\Search\SearchResultDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SearchResultResource extends JsonResource
{
    public function __construct(private readonly SearchResultDTO $result)
    {
        parent::__construct($result);
    }

    public function toArray(Request $request): array
    {
        return [
            'data' => ProductSearchResource::collection(collect($this->result->hits)),
            'meta' => [
                'total' => $this->result->total,
                'page' => $this->result->page,
                'per_page' => $this->result->perPage,
                'last_page' => $this->result->lastPage(),
                'took_ms' => $this->result->tookMs,
            ],
            'aggregations' => $this->result->aggregations,
        ];
    }
}