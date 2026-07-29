import Alpine from 'alpinejs';
import debounce from 'lodash/debounce';

const API_BASE = import.meta.env.VITE_API_URL ?? '/api';

function readParams() {
    const p = new URLSearchParams(window.location.search);
    return {
        q: p.get('q') || '',
        brand_ids: readIdList(p, 'brand_ids'),
        category_ids: readIdList(p, 'category_ids'),
        min_price: p.get('min_price') ? Number(p.get('min_price')) : null,
        max_price: p.get('max_price') ? Number(p.get('max_price')) : null,
        min_rating: p.get('min_rating') ? Number(p.get('min_rating')) : null,
        in_stock: p.get('in_stock') === '1',
        sort_by: p.get('sort_by') || 'relevance',
        sort_direction: p.get('sort_direction') || 'desc',
        page: Number(p.get('page') || 1),
        per_page: Number(p.get('per_page') || 24),
    };
}

function readIdList(params, key) {
    const values = [...params.getAll(`${key}[]`), ...params.getAll(key)];

    return [...new Set(values
        .flatMap((value) => String(value).split(','))
        .map((value) => Number(value.trim()))
        .filter((value) => Number.isInteger(value) && value > 0))];
}

function hasPriceValue(value) {
    return value !== null && value !== undefined && value !== '';
}

function effectiveMinPrice(filters) {
    return hasPriceValue(filters.min_price) ? filters.min_price : 0;
}

function writeParams(filters) {
    const params = new URLSearchParams();
    if (filters.q) params.set('q', filters.q);
    filters.brand_ids.forEach((id) => params.append('brand_ids[]', id));
    filters.category_ids.forEach((id) => params.append('category_ids[]', id));
    if (hasPriceValue(filters.min_price) || hasPriceValue(filters.max_price)) params.set('min_price', effectiveMinPrice(filters));
    if (hasPriceValue(filters.max_price)) params.set('max_price', filters.max_price);
    if (filters.min_rating !== null) params.set('min_rating', filters.min_rating);
    if (filters.in_stock) params.set('in_stock', '1');
    if (filters.sort_by !== 'relevance') params.set('sort_by', filters.sort_by);
    if (filters.sort_direction !== 'desc') params.set('sort_direction', filters.sort_direction);
    if (filters.page > 1) params.set('page', filters.page);
    if (filters.per_page !== 24) params.set('per_page', filters.per_page);

    const url = `${window.location.pathname}?${params.toString()}`;
    window.history.pushState({}, '', url);
}

function buildQuery(filters) {
    const params = new URLSearchParams();
    if (filters.q) params.set('q', filters.q);
    filters.brand_ids.forEach((id) => params.append('brand_ids[]', id));
    filters.category_ids.forEach((id) => params.append('category_ids[]', id));
    if (hasPriceValue(filters.min_price) || hasPriceValue(filters.max_price)) params.set('min_price', effectiveMinPrice(filters));
    if (hasPriceValue(filters.max_price)) params.set('max_price', filters.max_price);
    if (filters.min_rating !== null) params.set('min_rating', filters.min_rating);
    if (filters.in_stock) params.set('in_stock', '1');
    params.set('sort_by', filters.sort_by);
    params.set('sort_direction', filters.sort_direction);
    params.set('page', filters.page);
    params.set('per_page', filters.per_page);
    return params.toString();
}

window.searchPage = function () {
    return {
        query: '',
        filters: readParams(),
        products: [],
        aggregations: { brands: [], categories: [], rating_ranges: [], price: {} },
        meta: { total: null, page: 1, last_page: 1, per_page: 24 },
        loading: false,
        error: false,
        suggestions: [],
        suggestLoading: false,
        showSuggestions: false,
        sortValue: 'relevance-desc',

        init() {
            this.query = this.filters.q || '';
            this.sortValue = `${this.filters.sort_by}-${this.filters.sort_direction}`;
            this._debouncedFetchSuggestions = debounce(() => this._loadSuggestions(), 200);
            this.search();

            window.addEventListener('popstate', () => {
                this.filters = readParams();
                this.query = this.filters.q || '';
                this.search();
            });
        },

        async search() {
            this.loading = true;
            this.error = false;
            try {
                const res = await fetch(`${API_BASE}/search/products?${buildQuery(this.filters)}`, {
                    headers: { Accept: 'application/json' },
                });
                if (!res.ok) throw new Error('Request failed');
                const data = await res.json();
                this.products = data.data;
                this.meta = data.meta;
                this.aggregations = data.aggregations;
            } catch (e) {
                this.error = true;
            } finally {
                this.loading = false;
            }
        },

        submitSearch() {
            this.showSuggestions = false;
            this.filters.q = this.query.trim() || undefined;
            this.resetPageAndSearch();
        },

        fetchSuggestions() {
            this.showSuggestions = true;
            if (this.query.trim().length === 0) {
                this.suggestions = [];
                return;
            }
            this._debouncedFetchSuggestions();
        },

        async _loadSuggestions() {
            this.suggestLoading = true;
            try {
                const res = await fetch(`${API_BASE}/search/suggest?q=${encodeURIComponent(this.query)}&limit=8`, {
                    headers: { Accept: 'application/json' },
                });
                const data = await res.json();
                this.suggestions = data.data;
            } catch (e) {
                this.suggestions = [];
            } finally {
                this.suggestLoading = false;
            }
        },

        selectSuggestion(s) {
            this.query = s.text;
            this.showSuggestions = false;
            if (s.id) {
                fetch(`${API_BASE}/search/suggestion-click`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                    body: JSON.stringify({ q: s.text, product_id: s.id }),
                }).catch(() => {});
            }
            if (s.slug) {
                window.location.href = `/products/${s.slug}`;
                return;
            }
            this.submitSearch();
        },

        toggleArrayFilter(key, id) {
            const list = this.filters[key];
            this.filters[key] = list.includes(id) ? list.filter((x) => x !== id) : [...list, id];
            this.resetPageAndSearch();
        },

        applySort() {
            const [sortBy, sortDirection] = this.sortValue.split('-');
            this.filters.sort_by = sortBy;
            this.filters.sort_direction = sortDirection;
            this.persistAndSearch();
        },

        goToPage(page) {
            if (page < 1 || page > this.meta.last_page) return;
            this.filters.page = page;
            this.persistAndSearch();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        pageNumbers() {
            const current = this.meta.page;
            const last = this.meta.last_page;
            const delta = 1;
            const range = [1];
            const left = Math.max(2, current - delta);
            const right = Math.min(last - 1, current + delta);
            if (left > 2) range.push('...');
            for (let i = left; i <= right; i++) range.push(i);
            if (right < last - 1) range.push('...');
            if (last > 1) range.push(last);
            return range;
        },

        resetPageAndSearch() {
            this.filters.page = 1;
            this.persistAndSearch();
        },

        persistAndSearch() {
            writeParams(this.filters);
            this.search();
        },

        clearFilters() {
            this.filters = {
                q: undefined,
                brand_ids: [],
                category_ids: [],
                min_price: null,
                max_price: null,
                min_rating: null,
                in_stock: false,
                sort_by: 'relevance',
                sort_direction: 'desc',
                page: 1,
                per_page: 24,
            };
            this.query = '';
            this.sortValue = 'relevance-desc';
            this.persistAndSearch();
        },
    };
};

Alpine.start();
