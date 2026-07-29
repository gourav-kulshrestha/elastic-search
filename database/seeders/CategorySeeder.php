<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class CategorySeeder extends Seeder
{
    private const TREE = [
        'Electronics' => ['Smartphones', 'Laptops', 'Headphones', 'Cameras', 'Smart Home'],
        'Fashion' => ['Men', 'Women', 'Kids', 'Shoes', 'Accessories'],
        'Home & Kitchen' => ['Furniture', 'Cookware', 'Bedding', 'Decor', 'Appliances'],
        'Sports & Outdoors' => ['Fitness', 'Camping', 'Cycling', 'Team Sports'],
        'Beauty & Personal Care' => ['Skincare', 'Makeup', 'Hair Care', 'Fragrances'],
        'Toys & Games' => ['Action Figures', 'Board Games', 'Educational', 'Outdoor Play'],
        'Books' => ['Fiction', 'Non-Fiction', 'Children', 'Comics'],
    ];

    public function run(): void
    {
        foreach (self::TREE as $parentName => $children) {
            $parent = Category::query()->create([
                'parent_id' => null,
                'name' => $parentName,
                'slug' => Str::slug($parentName),
                'path' => $parentName,
            ]);

            foreach ($children as $childName) {
                Category::query()->create([
                    'parent_id' => $parent->id,
                    'name' => $childName,
                    'slug' => Str::slug("{$parentName}-{$childName}"),
                    'path' => "{$parentName}/{$childName}",
                ]);
            }
        }
    }
}