<?php

use App\Http\Controllers\ProductPageController;
use App\Http\Controllers\SearchPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', SearchPageController::class)->name('search');
Route::redirect('/search', '/', 301);
Route::get('/products/{slug}', ProductPageController::class)->name('products.show');
