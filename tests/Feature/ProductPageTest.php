<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class ProductPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_detail_page_renders_by_slug(): void
    {
        Queue::fake();

        $brand = Brand::factory()->create(['name' => 'Acme Audio']);
        $category = Category::factory()->create([
            'name' => 'Headphones',
            'path' => 'Electronics/Headphones',
        ]);
        $product = Product::factory()->create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Studio Headphones',
            'slug' => 'studio-headphones',
            'sku' => 'AUD-123456',
            'price' => 199.99,
            'sale_price' => 149.99,
            'stock' => 12,
        ]);

        Product::factory()->create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Travel Headphones',
            'slug' => 'travel-headphones',
            'popularity' => 100,
        ]);

        $this->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertSee('Studio Headphones')
            ->assertSee('Acme Audio')
            ->assertSee('AUD-123456')
            ->assertSee('$149.99')
            ->assertSee('Travel Headphones');
    }

    public function test_product_detail_page_returns_404_for_unknown_slug(): void
    {
        $this->get(route('products.show', 'missing-product'))->assertNotFound();
    }
}
