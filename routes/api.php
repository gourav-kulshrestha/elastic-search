<?php

use App\Http\Controllers\Api\Search\SearchAnalyticsController;
use App\Http\Controllers\Api\Search\SearchController;
use App\Http\Controllers\Api\Search\SuggestController;
use App\Http\Controllers\Api\Search\SuggestionClickController;
use Illuminate\Support\Facades\Route;

Route::prefix('search')->group(function () {
    Route::get('/products', SearchController::class)
        ->middleware('throttle:60,1')
        ->name('search.products');

    Route::get('/suggest', SuggestController::class)
        ->middleware('throttle:120,1')
        ->name('search.suggest');

    Route::post('/suggestion-click', SuggestionClickController::class)
        ->middleware('throttle:60,1')
        ->name('search.suggestion-click');

    Route::middleware(['auth:sanctum', 'can:view-search-analytics'])->group(function () {
        Route::get('/analytics/top-queries', [SearchAnalyticsController::class, 'topQueries'])
            ->name('search.analytics.top-queries');

        Route::get('/analytics/zero-results', [SearchAnalyticsController::class, 'zeroResultQueries'])
            ->name('search.analytics.zero-results');
    });
});