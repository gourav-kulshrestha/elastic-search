<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_logs', function (Blueprint $table) {
            $table->id();
            $table->string('query', 255);
            $table->unsignedInteger('results_count')->default(0);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('filters')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['query']);
            $table->index(['created_at']);
        });

        Schema::create('search_suggestion_clicks', function (Blueprint $table) {
            $table->id();
            $table->string('query', 255);
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['query']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_suggestion_clicks');
        Schema::dropIfExists('search_logs');
    }
};