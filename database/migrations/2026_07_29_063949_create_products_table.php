<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('brands')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();

            $table->string('name', 255);
            $table->string('slug', 280)->unique();
            $table->string('sku', 64)->unique();
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();

            $table->decimal('price', 10, 2);
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->decimal('rating', 3, 2)->default(0);
            $table->unsignedInteger('reviews_count')->default(0);
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('popularity')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Composite indexes matched to common MySQL fallback queries
            // (e.g. admin listing/search outside Elasticsearch) and to keep
            // relation loads for indexing fast at scale.
            $table->index(['brand_id', 'category_id']);
            $table->index(['category_id', 'price']);
            $table->index('popularity');
            $table->index('rating');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};