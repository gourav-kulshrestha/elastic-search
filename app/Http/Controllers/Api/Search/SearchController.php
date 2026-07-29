<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Search;

use App\Contracts\Search\ProductSearchServiceInterface;
use App\Contracts\Search\SearchAnalyticsServiceInterface;
use App\DTOs\Search\SearchFiltersDTO;
use App\Exceptions\Search\SearchException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Search\SearchProductsRequest;
use App\Http\Resources\Search\SearchResultResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

final class SearchController extends Controller
{
    public function __construct(
        private readonly ProductSearchServiceInterface $searchService,
        private readonly SearchAnalyticsServiceInterface $analytics,
    ) {
    }

    public function __invoke(SearchProductsRequest $request): JsonResponse
    {
        $filters = SearchFiltersDTO::fromArray($request->toFiltersArray());

        try {
            $result = $this->searchService->search($filters);
        } catch (SearchException $e) {
            Log::error('Product search failed', ['message' => $e->getMessage()]);

            return response()->json([
                'message' => 'Search is temporarily unavailable. Please try again shortly.',
            ], 503);
        }

        if (! empty($filters->query)) {
            $this->analytics->logSearch(
                query: $filters->query,
                resultsCount: $result->total,
                userId: $filters->userId,
                filters: [
                    'brand_ids' => $filters->brandIds,
                    'category_ids' => $filters->categoryIds,
                    'min_price' => $filters->minPrice,
                    'max_price' => $filters->maxPrice,
                    'min_rating' => $filters->minRating,
                ],
            );
        }

        return (new SearchResultResource($result))
            ->response()
            ->setStatusCode(200);
    }
}