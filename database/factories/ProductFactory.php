<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
final class ProductFactory extends Factory
{
    protected $model = Product::class;

    private const ADJECTIVES = ['Pro', 'Max', 'Ultra', 'Lite', 'Plus', 'Mini', 'Air', 'Edge', 'Core', 'Elite'];

    public function definition(): array
    {
        $baseName = ucwords($this->faker->words(rand(2, 4), true));
        $suffix = $this->faker->boolean(40) ? ' ' . $this->faker->randomElement(self::ADJECTIVES) : '';
        $name = "{$baseName}{$suffix}";

        $price = $this->faker->randomFloat(2, 9.99, 1999.99);
        $onSale = $this->faker->boolean(30);
        $salePrice = $onSale ? round($price * $this->faker->randomFloat(2, 0.5, 0.9), 2) : null;

        return [
            'brand_id' => Brand::factory(),
            'category_id' => Category::factory(),
            'name' => $name,
            'slug' => Str::slug($name) . '-' . $this->faker->unique()->numberBetween(10000, 99999),
            'sku' => strtoupper(Str::random(3)) . '-' . $this->faker->unique()->numberBetween(100000, 999999),
            'description' => $this->faker->paragraphs(3, true),
            'image_url' => "https://picsum.photos/seed/{$this->faker->uuid()}/600/600",
            'price' => $price,
            'sale_price' => $salePrice,
            'rating' => $this->faker->randomFloat(2, 2.5, 5.0),
            'reviews_count' => $this->faker->numberBetween(0, 5000),
            'stock' => $this->faker->numberBetween(0, 500),
            'popularity' => $this->faker->numberBetween(0, 1000),
            'created_at' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'updated_at' => now(),
        ];
    }

    public function outOfStock(): self
    {
        return $this->state(fn () => ['stock' => 0]);
    }

    public function highlyRated(): self
    {
        return $this->state(fn () => [
            'rating' => $this->faker->randomFloat(2, 4.3, 5.0),
            'reviews_count' => $this->faker->numberBetween(500, 10000),
        ]);
    }
}