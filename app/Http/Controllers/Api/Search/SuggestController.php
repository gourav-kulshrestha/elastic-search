<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Search;

use App\Contracts\Search\SuggestServiceInterface;
use App\Exceptions\Search\SearchException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Search\SuggestRequest;
use App\Http\Resources\Search\SuggestionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

final class SuggestController extends Controller
{
    public function __construct(private readonly SuggestServiceInterface $suggestService)
    {
    }

    public function __invoke(SuggestRequest $request): JsonResponse
    {
        try {
            $suggestions = $this->suggestService->suggest(
                query: (string) $request->validated('q'),
                limit: (int) $request->validated('limit', 8),
            );
        } catch (SearchException $e) {
            Log::error('Suggest query failed', ['message' => $e->getMessage()]);

            return response()->json(['data' => []], 200);
        }

        return response()->json([
            'data' => SuggestionResource::collection(collect($suggestions)),
        ]);
    }
}