<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Search;

use App\Contracts\Search\SearchAnalyticsServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Search\SuggestionClickRequest;
use Illuminate\Http\JsonResponse;

final class SuggestionClickController extends Controller
{
    public function __construct(private readonly SearchAnalyticsServiceInterface $analytics)
    {
    }

    public function __invoke(SuggestionClickRequest $request): JsonResponse
    {
        $this->analytics->logSuggestionClick(
            query: (string) $request->validated('q'),
            productId: (int) $request->validated('product_id'),
            userId: $request->user()?->id,
        );

        return response()->json(status: 204);
    }
}