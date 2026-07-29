@extends('layouts.app')

@section('title', $product->name . ' - Product Details')

@php
    $price = (float) $product->price;
    $salePrice = $product->sale_price !== null ? (float) $product->sale_price : null;
    $effectivePrice = $salePrice ?? $price;
    $discountPercent = $salePrice !== null && $price > 0 ? (int) round((1 - $salePrice / $price) * 100) : null;
@endphp

@section('content')
<main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    <nav class="mb-5 flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('search') }}" class="font-medium text-gray-700 hover:text-gray-950">Search</a>
        <span>/</span>
        @if ($product->category)
            <a href="{{ route('search') }}?category_ids[]={{ $product->category_id }}" class="hover:text-gray-950">{{ $product->category->name }}</a>
            <span>/</span>
        @endif
        <span class="truncate text-gray-400">{{ $product->name }}</span>
    </nav>

    <section class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(360px,440px)]">
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
            @if ($product->image_url)
                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="aspect-square w-full object-cover">
            @else
                <div class="flex aspect-square w-full items-center justify-center bg-gray-100 text-sm font-medium text-gray-400">
                    No image available
                </div>
            @endif
        </div>

        <div class="space-y-6">
            <div>
                @if ($product->brand)
                    <a href="{{ route('search') }}?brand_ids[]={{ $product->brand_id }}" class="text-xs font-semibold uppercase tracking-wide text-gray-500 hover:text-gray-900">
                        {{ $product->brand->name }}
                    </a>
                @endif
                <h1 class="mt-2 text-3xl font-semibold tracking-normal text-gray-950">{{ $product->name }}</h1>
                <p class="mt-2 text-sm text-gray-500">SKU {{ $product->sku }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <span class="text-3xl font-semibold text-gray-950">${{ number_format($effectivePrice, 2) }}</span>
                @if ($salePrice !== null)
                    <span class="text-lg text-gray-400 line-through">${{ number_format($price, 2) }}</span>
                    <span class="rounded-md bg-red-50 px-2 py-1 text-sm font-semibold text-red-600">{{ $discountPercent }}% off</span>
                @endif
            </div>

            <div class="flex flex-wrap items-center gap-4 text-sm">
                <div class="flex items-center gap-1">
                    <span class="text-amber-400">★</span>
                    <span class="font-medium text-gray-900">{{ number_format((float) $product->rating, 1) }}</span>
                    <span class="text-gray-500">({{ number_format($product->reviews_count) }} reviews)</span>
                </div>
                @if ($product->category)
                    <a href="{{ route('search') }}?category_ids[]={{ $product->category_id }}" class="text-gray-500 hover:text-gray-900">
                        {{ $product->category->path ?? $product->category->name }}
                    </a>
                @endif
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-4">
                @if ($product->stock > 0)
                    <p class="font-medium text-emerald-700">In stock</p>
                    <p class="mt-1 text-sm text-gray-500">{{ number_format($product->stock) }} available</p>
                @else
                    <p class="font-medium text-red-700">Out of stock</p>
                    <p class="mt-1 text-sm text-gray-500">Check back soon for availability.</p>
                @endif
            </div>

            @if ($product->description)
                <section>
                    <h2 class="text-base font-semibold text-gray-950">Description</h2>
                    <p class="mt-3 whitespace-pre-line text-sm leading-6 text-gray-600">{{ $product->description }}</p>
                </section>
            @endif
        </div>
    </section>

    @if ($relatedProducts->isNotEmpty())
        <section class="mt-12">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-950">Related Products</h2>
                @if ($product->category)
                    <a href="{{ route('search') }}?category_ids[]={{ $product->category_id }}" class="text-sm font-medium text-gray-500 hover:text-gray-900">View category</a>
                @endif
            </div>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($relatedProducts as $related)
                    @php
                        $relatedPrice = (float) ($related->sale_price ?? $related->price);
                    @endphp
                    <a href="{{ route('products.show', $related->slug) }}" class="group overflow-hidden rounded-lg border border-gray-200 bg-white hover:shadow-md">
                        @if ($related->image_url)
                            <img src="{{ $related->image_url }}" alt="{{ $related->name }}" class="aspect-square w-full object-cover transition duration-300 group-hover:scale-105">
                        @else
                            <div class="aspect-square w-full bg-gray-100"></div>
                        @endif
                        <div class="space-y-1 p-3">
                            <p class="truncate text-xs font-medium uppercase text-gray-400">{{ $related->brand?->name }}</p>
                            <h3 class="line-clamp-2 text-sm font-medium text-gray-900">{{ $related->name }}</h3>
                            <p class="text-sm font-semibold text-gray-950">${{ number_format($relatedPrice, 2) }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</main>
@endsection
