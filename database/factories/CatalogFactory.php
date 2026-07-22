<?php

namespace Database\Factories;

use App\Models\Catalog;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Catalog> */
class CatalogFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'category_id' => Category::factory(),
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 9999),
            'description' => fake()->paragraph(3),
            'preview_url' => fake()->url(),
            'base_price' => fake()->numberBetween(125000, 950000),
            'estimated_days' => fake()->numberBetween(2, 7),
            'rating_average' => fake()->randomFloat(2, 4, 5),
            'reviews_count' => fake()->numberBetween(0, 40),
            'is_featured' => fake()->boolean(35),
            'is_active' => true,
        ];
    }
}
