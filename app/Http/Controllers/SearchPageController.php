<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\View\View;

final class SearchPageController extends Controller
{
    public function __invoke(): View
    {
        return view('search.index');
    }
}