<?php

declare(strict_types=1);

namespace App\Http\Requests\Search;

use Illuminate\Foundation\Http\FormRequest;

final class SearchProductsRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $minPrice = $this->input('min_price');
        $maxPrice = $this->input('max_price');

        $data = [
            'brand_ids' => $this->normalizeIdList($this->input('brand_ids', $this->input('brand_id'))),
            'category_ids' => $this->normalizeIdList($this->input('category_ids', $this->input('category_id'))),
        ];

        if (($minPrice === null || $minPrice === '') && $maxPrice !== null && $maxPrice !== '') {
            $data['min_price'] = 0;
        }

        $this->merge($data);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['sometimes', 'nullable', 'string', 'max:200'],
            'brand_ids' => ['sometimes', 'array'],
            'brand_ids.*' => ['integer', 'min:1'],
            'category_ids' => ['sometimes', 'array'],
            'category_ids.*' => ['integer', 'min:1'],
            'min_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'max_price' => ['sometimes', 'nullable', 'numeric', 'min:0', 'gte:min_price'],
            'min_rating' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:5'],
            'in_stock' => ['sometimes', 'boolean'],
            'sort_by' => ['sometimes', 'string', 'in:relevance,price,rating,newest,popularity'],
            'sort_direction' => ['sometimes', 'string', 'in:asc,desc'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function toFiltersArray(): array
    {
        $data = $this->validated();
        $data['user_id'] = $this->user()?->id;

        return $data;
    }

    /**
     * @return array<int, mixed>
     */
    private function normalizeIdList(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $values = is_array($value) ? $value : [$value];
        $normalized = [];

        foreach ($values as $item) {
            $items = is_array($item) ? $item : [$item];

            foreach ($items as $id) {
                foreach (is_string($id) ? explode(',', $id) : [$id] as $part) {
                    $part = is_string($part) ? trim($part) : $part;

                    if ($part === null || $part === '') {
                        continue;
                    }

                    $normalized[] = $part;
                }
            }
        }

        return array_values(array_unique($normalized, SORT_REGULAR));
    }
}
