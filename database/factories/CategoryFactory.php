<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
final class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);
        $name = ucwords($name);

        return [
            'parent_id' => null,
            'name' => $name,
            'slug' => Str::slug($name) . '-' . $this->faker->unique()->numberBetween(1000, 9999),
            'path' => $name,
        ];
    }

    public function child(Category $parent): self
    {
        return $this->state(function () use ($parent) {
            $name = ucwords($this->faker->unique()->words(2, true));

            return [
                'parent_id' => $parent->id,
                'name' => $name,
                'path' => "{$parent->path}/{$name}",
            ];
        });
    }
}