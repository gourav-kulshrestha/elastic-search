@extends('layouts.app')

@section('title', 'Search Products')

@section('content')
<div x-data="searchPage()" x-init="init()" class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">

    {{-- Search bar --}}
    <div class="relative mb-6 max-w-2xl" @click.outside="showSuggestions = false">
        <div class="flex items-center gap-2 rounded-full border border-gray-300 bg-white px-4 py-2.5 shadow-sm focus-within:border-orange-400 focus-within:ring-2 focus-within:ring-orange-100">
            <svg class="h-5 w-5 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/></svg>
            <input
                type="text"
                x-model="query"
                @input.debounce.300ms="fetchSuggestions()"
                @focus="showSuggestions = true"
                @keydown.enter="submitSearch()"
                @keydown.escape="showSuggestions = false"
                placeholder="Search products, brands, and categories"
                class="w-full bg-transparent text-sm outline-none placeholder:text-gray-400"
            >
            <button x-show="query" @click="query = ''; showSuggestions = false" class="text-gray-400 hover:text-gray-600">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Autocomplete dropdown --}}
        <div x-show="showSuggestions && query.length > 0" x-cloak
             class="absolute z-50 mt-2 w-full overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg">
            <template x-if="suggestLoading && suggestions.length === 0">
                <div class="space-y-2 p-3">
                    <template x-for="i in 4"><div class="h-8 animate-pulse rounded-md bg-gray-100"></div></template>
                </div>
            </template>
            <template x-if="!suggestLoading && suggestions.length === 0">
                <p class="px-4 py-3 text-sm text-gray-500">No matches found.</p>
            </template>
            <ul>
                <template x-for="(s, index) in suggestions" :key="index">
                    <li>
                        <button type="button"
                            @click="selectSuggestion(s)"
                            class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm hover:bg-gray-50">
                            <img x-show="s.image" :src="s.image" class="h-8 w-8 shrink-0 rounded-md object-cover">
                            <span class="flex-1 truncate text-gray-800" x-text="s.text"></span>
                            <span x-show="s.price" class="text-xs font-medium text-gray-500" x-text="'$' + (s.price ?? 0).toFixed(2)"></span>
                        </button>
                    </li>
                </template>
            </ul>
        </div>
    </div>

    <div class="flex flex-col gap-8 lg:flex-row">

        {{-- Filters sidebar --}}
        <aside class="w-full shrink-0 lg:w-64">
            <div class="flex items-center justify-between pb-2">
                <h2 class="text-base font-semibold">Filters</h2>
                <button @click="clearFilters()" class="text-xs font-medium text-gray-500 hover:text-gray-800">Clear all</button>
            </div>

            <div class="border-b border-gray-100 py-4">
                <h3 class="mb-3 text-sm font-semibold">Availability</h3>
                <label class="flex cursor-pointer items-center gap-2.5 text-sm">
                    <input type="checkbox" x-model="filters.in_stock" @change="resetPageAndSearch()" class="h-4 w-4 rounded border-gray-300">
                    <span class="text-gray-700">In stock only</span>
                </label>
            </div>

            <div class="border-b border-gray-100 py-4">
                <h3 class="mb-3 text-sm font-semibold">Price</h3>
                <div class="flex items-center gap-2">
                    <input type="number" x-model.number="filters.min_price" @change.debounce.400ms="resetPageAndSearch()"
                           placeholder="Min" class="w-full rounded-md border border-gray-200 px-2 py-1.5 text-sm">
                    <span class="text-gray-400">–</span>
                    <input type="number" x-model.number="filters.max_price" @change.debounce.400ms="resetPageAndSearch()"
                           placeholder="Max" class="w-full rounded-md border border-gray-200 px-2 py-1.5 text-sm">
                </div>
            </div>

            <div class="border-b border-gray-100 py-4">
                <h3 class="mb-3 text-sm font-semibold">Rating</h3>
                <template x-for="rating in [4,3,2,1]" :key="rating">
                    <button @click="filters.min_rating = (filters.min_rating === rating ? null : rating); resetPageAndSearch()"
                        class="flex w-full items-center gap-1.5 rounded-md px-2 py-1.5 text-sm hover:bg-gray-50"
                        :class="filters.min_rating === rating ? 'bg-gray-100' : ''">
                        <span class="text-amber-500" x-text="'★'.repeat(rating) + '☆'.repeat(5-rating)"></span>
                        <span class="text-gray-600">& up</span>
                    </button>
                </template>
            </div>

            <div class="border-b border-gray-100 py-4">
                <h3 class="mb-3 text-sm font-semibold">Brand</h3>
                <template x-for="brand in aggregations.brands" :key="brand.id">
                    <label class="flex cursor-pointer items-center gap-2.5 py-1 text-sm">
                        <input type="checkbox" :value="brand.id"
                            :checked="filters.brand_ids.includes(brand.id)"
                            @change="toggleArrayFilter('brand_ids', brand.id)"
                            class="h-4 w-4 rounded border-gray-300">
                        <span class="flex-1 truncate text-gray-700" x-text="brand.label"></span>
                        <span class="text-xs text-gray-400" x-text="brand.count"></span>
                    </label>
                </template>
            </div>

            <div class="py-4">
                <h3 class="mb-3 text-sm font-semibold">Category</h3>
                <template x-for="cat in aggregations.categories" :key="cat.id">
                    <label class="flex cursor-pointer items-center gap-2.5 py-1 text-sm">
                        <input type="checkbox" :value="cat.id"
                            :checked="filters.category_ids.includes(cat.id)"
                            @change="toggleArrayFilter('category_ids', cat.id)"
                            class="h-4 w-4 rounded border-gray-300">
                        <span class="flex-1 truncate text-gray-700" x-text="cat.label"></span>
                        <span class="text-xs text-gray-400" x-text="cat.count"></span>
                    </label>
                </template>
            </div>
        </aside>

        {{-- Results --}}
        <div class="flex-1">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-gray-500">
                    <span x-show="meta.total !== null">
                        <span class="font-medium text-gray-800" x-text="meta.total?.toLocaleString()"></span> results
                        <template x-if="filters.q"> for "<span class="font-medium text-gray-800" x-text="filters.q"></span>"</template>
                    </span>
                </p>
                <select x-model="sortValue" @change="applySort()" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm">
                    <option value="relevance-desc">Best match</option>
                    <option value="price-asc">Price: Low to High</option>
                    <option value="price-desc">Price: High to Low</option>
                    <option value="rating-desc">Avg. Customer Rating</option>
                    <option value="newest-desc">Newest Arrivals</option>
                    <option value="popularity-desc">Best Sellers</option>
                </select>
            </div>

            {{-- Error --}}
            <div x-show="error" x-cloak class="flex flex-col items-center gap-3 rounded-xl border border-red-100 bg-red-50 py-20 text-center">
                <p class="text-base font-medium text-red-700">Something went wrong</p>
                <p class="text-sm text-red-500">We couldn't load search results. Please try again.</p>
                <button @click="search()" class="rounded-full bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-500">Retry</button>
            </div>

            {{-- Loading skeleton --}}
            <div x-show="loading && !error" x-cloak class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                <template x-for="i in 12">
                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                        <div class="aspect-square w-full animate-pulse bg-gray-100"></div>
                        <div class="space-y-2 p-3">
                            <div class="h-3 w-1/3 animate-pulse rounded bg-gray-100"></div>
                            <div class="h-4 w-full animate-pulse rounded bg-gray-100"></div>
                            <div class="h-5 w-1/2 animate-pulse rounded bg-gray-100"></div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Empty --}}
            <div x-show="!loading && !error && products.length === 0" x-cloak
                 class="flex flex-col items-center gap-3 rounded-xl border border-dashed border-gray-300 py-20 text-center">
                <p class="text-base font-medium text-gray-700">No products found</p>
                <p class="max-w-sm text-sm text-gray-500">Try removing some filters or checking your spelling.</p>
                <button @click="clearFilters()" class="mt-2 rounded-full bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">Clear all filters</button>
            </div>

            {{-- Product grid --}}
            <div x-show="!loading && !error && products.length > 0" x-cloak class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                <template x-for="product in products" :key="product.id">
                    <a :href="'/products/' + product.slug" class="group flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white hover:shadow-md">
                        <div class="relative aspect-square w-full overflow-hidden bg-gray-50">
                            <img x-show="product.image_url" :src="product.image_url" :alt="product.name"
                                 class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                            <span x-show="product.discount_percent" class="absolute left-2 top-2 rounded-md bg-red-500 px-2 py-0.5 text-xs font-semibold text-white"
                                  x-text="'-' + product.discount_percent + '%'"></span>
                            <div x-show="!product.in_stock" class="absolute inset-0 flex items-center justify-center bg-white/70">
                                <span class="rounded-md bg-gray-900/80 px-3 py-1 text-xs font-medium text-white">Out of stock</span>
                            </div>
                        </div>
                        <div class="flex flex-1 flex-col gap-1 p-3">
                            <span x-show="product.brand" class="text-xs font-medium uppercase text-gray-400" x-text="product.brand?.name"></span>
                            <h3 class="line-clamp-2 text-sm font-medium text-gray-900 [&_mark]:bg-orange-100" x-html="product.highlight?.name || product.name"></h3>
                            <div class="mt-1 flex items-center gap-1 text-xs text-gray-600">
                                <span class="text-amber-400">★</span>
                                <span x-text="product.rating.toFixed(1)"></span>
                                <span class="text-gray-400" x-text="'(' + product.reviews_count + ')'"></span>
                            </div>
                            <div class="mt-auto flex items-baseline gap-2 pt-2">
                                <span class="text-base font-semibold" x-text="'$' + (product.sale_price ?? product.price).toFixed(2)"></span>
                                <span x-show="product.sale_price" class="text-xs text-gray-400 line-through" x-text="'$' + product.price.toFixed(2)"></span>
                            </div>
                        </div>
                    </a>
                </template>
            </div>

            {{-- Pagination --}}
            <div x-show="meta.last_page > 1" x-cloak class="flex items-center justify-center gap-1 pt-6">
                <button @click="goToPage(meta.page - 1)" :disabled="meta.page <= 1"
                    class="flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 disabled:opacity-40">‹</button>
                <template x-for="p in pageNumbers()" :key="p">
                    <button @click="typeof p === 'number' && goToPage(p)"
                        :class="p === meta.page ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-50'"
                        class="flex h-9 w-9 items-center justify-center rounded-full text-sm font-medium"
                        x-text="p"></button>
                </template>
                <button @click="goToPage(meta.page + 1)" :disabled="meta.page >= meta.last_page"
                    class="flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 disabled:opacity-40">›</button>
            </div>
        </div>
    </div>
</div>
@endsection