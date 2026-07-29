<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Search;

use App\Contracts\Search\SearchAnalyticsServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Admin-only endpoint (protected by 'auth:sanctum' + 'can:view-analytics' in routes)
 * for reviewing search performance.
 */
final class SearchAnalyticsController extends Controller
{
    public function __construct(private readonly SearchAnalyticsServiceInterface $analytics)
    {
    }

    public function topQueries(Request $request): JsonResponse
    {
        $limit = (int) $request->integer('limit', 20);
        $days = (int) $request->integer('days', 7);

        return response()->json(['data' => $this->analytics->getTopQueries($limit, $days)]);
    }

    public function zeroResultQueries(Request $request): JsonResponse
    {
        $limit = (int) $request->integer('limit', 20);
        $days = (int) $request->integer('days', 7);

        return response()->json(['data' => $this->analytics->getZeroResultQueries($limit, $days)]);
    }
}