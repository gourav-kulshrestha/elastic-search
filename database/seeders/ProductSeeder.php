<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

final class ProductSeeder extends Seeder
{
    private const TOTAL_PRODUCTS = 20000;
    private const CHUNK_SIZE = 200;

    public function run(): void
    {
        /** @var Collection<int, int> $brandIds */
        $brandIds = Brand::query()->pluck('id');

        // Only leaf categories (those with a parent) receive products —
        // mirrors real catalogs where items live at the most specific level.
        /** @var Collection<int, int> $categoryIds */
        $categoryIds = Category::query()->whereNotNull('parent_id')->pluck('id');

        if ($brandIds->isEmpty() || $categoryIds->isEmpty()) {
            $this->command?->warn('Run BrandSeeder and CategorySeeder before ProductSeeder.');
            return;
        }

        $remaining = self::TOTAL_PRODUCTS;

        while ($remaining > 0) {
            $batchSize = min(self::CHUNK_SIZE, $remaining);

            Product::factory()
                ->count($batchSize)
                ->state(fn () => [
                    'brand_id' => $brandIds->random(),
                    'category_id' => $categoryIds->random(),
                ])
                ->create();

            $remaining -= $batchSize;
        }
    }
}