<?php

namespace Modules\Product\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Product\Catalog\Enums\CategoryStatus;
use Modules\Product\Catalog\Models\Category;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'parent_id' => null,
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $this->faker->optional()->sentence(),
            'position' => $this->faker->numberBetween(0, 100),
            'status' => $this->faker->randomElement([CategoryStatus::ACTIVE, CategoryStatus::INACTIVE]),
            'products_count' => 0,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CategoryStatus::ACTIVE,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CategoryStatus::INACTIVE,
        ]);
    }

    public function withParent(?Category $parent = null): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent?->id ?? Category::factory(),
        ]);
    }
}
